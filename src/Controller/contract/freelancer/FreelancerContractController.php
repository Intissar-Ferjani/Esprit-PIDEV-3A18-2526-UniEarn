<?php

namespace App\Controller\contract\freelancer;

use App\Repository\contract\ContractRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/freelancer/contracts')]
class FreelancerContractController extends AbstractController
{
    private function requireFreelancer(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'FREELANCER') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    // ── LIST FREELANCER CONTRACTS ───────────────────────────────────────

    #[Route('/', name: 'freelancer_contract_index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo, PaginatorInterface $paginator): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) throw $this->createNotFoundException('Freelancer not found.');

        $status = $request->query->get('status');
        $validStatuses = ['pending', 'signed'];
        if ($status && !in_array($status, $validStatuses, true)) {
            $status = null;
        }

        $contractsQuery = $repo->findByFreelancerId($freelancer->getIdFreelancer(), $status);
        $pagination = $paginator->paginate(
            $contractsQuery,
            $request->query->getInt('page', 1), /* page number */
            5 /* limit per page */
        );

        return $this->render('frontOffice/freelancer/contract/list-contracts.html.twig', [
            'contracts'     => $pagination,
            'currentStatus' => $status,
        ]);
    }

    // ── VIEW CONTRACT ───────────────────────────────────────────────────

    #[Route('/{id}', name: 'freelancer_contract_show', methods: ['GET'])]
    public function show(int $id, Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getFreelancer()->getIdFreelancer() !== $freelancer->getIdFreelancer()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        return $this->render('frontOffice/freelancer/contract/show-contract.html.twig', [
            'contract' => $contract,
        ]);
    }

    // ── SIGN CONTRACT (FREELANCER) ──────────────────────────────────────

    #[Route('/{id}/sign', name: 'freelancer_contract_sign', methods: ['POST'])]
    public function sign(int $id, Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getFreelancer()->getIdFreelancer() !== $freelancer->getIdFreelancer()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        $signatureData = $request->request->get('signature');
        if (!$signatureData || !str_starts_with($signatureData, 'data:image/png;base64,')) {
            $this->addFlash('error', 'Please draw your signature before submitting.');
            return $this->redirectToRoute('freelancer_contract_show', ['id' => $id]);
        }

        $contract->setFreelancerSignature(new \DateTime());
        $contract->setFreelancerSignatureImage($signatureData);
        $contract->setUpdatedAt(new \DateTime());

        if ($contract->getClientSignature() !== null) {
            $contract->setStatus('signed');
        }

        $em->flush();
        $this->addFlash('success', 'You have signed the contract.');

        return $this->redirectToRoute('freelancer_contract_show', ['id' => $id]);
    }

    // ── EXPORT PDF ──────────────────────────────────────────────────────

    #[Route('/{id}/pdf', name: 'freelancer_contract_pdf', methods: ['GET'])]
    public function pdf(int $id, Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getFreelancer()->getIdFreelancer() !== $freelancer->getIdFreelancer()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        if (!$contract->isSigned()) {
            $this->addFlash('error', 'Contract must be signed by both parties before exporting.');
            return $this->redirectToRoute('freelancer_contract_show', ['id' => $id]);
        }

        return $this->render('frontOffice/contract/pdf-contract.html.twig', [
            'contract' => $contract,
        ]);
    }

    // ── AI SUMMARY ──────────────────────────────────────────────────────

    #[Route('/{id}/ai-summary', name: 'freelancer_contract_ai_summary', methods: ['POST'])]
    public function aiSummary(int $id, Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo, \Symfony\Contracts\HttpClient\HttpClientInterface $client): \Symfony\Component\HttpFoundation\JsonResponse
    {
        if ($r = $this->requireFreelancer($request)) return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Unauthorized'], 401);

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getFreelancer()->getIdFreelancer() !== $freelancer->getIdFreelancer()) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Contract not found.'], 404);
        }
        
        $apiKey = $_ENV['GEMINI_API_KEY'] ?? $_ENV['OPENAI_API_KEY'] ?? '';

        if (!$apiKey) {
            // Simulation pour la démonstration (quand pas de clé API)
            sleep(2);
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'summary' => "<ul><li style='margin-bottom:8px'><strong>💰 Rémunération :</strong> Le montant total est fixé à $" . $contract->getAmount() . ".</li><li style='margin-bottom:8px'><strong>📅 Engagement :</strong> Le travail s'étend du " . $contract->getStartDate()->format('d/m/Y') . " au " . $contract->getEndDate()->format('d/m/Y') . ".</li><li style='margin-bottom:8px'><strong>ℹ️ Recommandation IA :</strong> Lisez attentivement toutes les clauses avant signature. <br><em style='font-size:11px;color:#a855f7;'>(Mode simulation : ajoutez GEMINI_API_KEY dans votre fichier .env pour activer la vraie analyse !)</em></li></ul>",
            ]);
        }

        try {
            $isGoogle = str_starts_with($apiKey, 'AIza'); // Google API Keys start with AIza

            // Build a rich prompt with ALL contract metadata so the AI always has data
            $contractText = "Titre du contrat : " . ($contract->getTitle() ?? 'Non spécifié') . "\n";
            $contractText .= "Montant : $" . ($contract->getAmount() ?? '0') . "\n";
            $contractText .= "Date de début : " . ($contract->getStartDate() ? $contract->getStartDate()->format('d/m/Y') : 'Non spécifiée') . "\n";
            $contractText .= "Date de fin : " . ($contract->getEndDate() ? $contract->getEndDate()->format('d/m/Y') : 'Non spécifiée') . "\n";
            $contractText .= "Client : " . ($contract->getClient() ? $contract->getClient()->getCompany() : 'Non spécifié') . "\n";
            $contractText .= "Statut : " . $contract->getStatus() . "\n";
            if ($contract->getContent()) {
                $contractText .= "\nContenu du contrat :\n" . $contract->getContent();
            }

            $prompt = "Agissez comme un avocat expert. Résumez ce contrat de freelance en français en 3 puces courtes et claires. Structurez votre réponse ainsi :\n1. Aspect financier (rémunération, modalités)\n2. Calendrier et délais (dates, durée)\n3. Points de vigilance critiques\n\nVoici les informations du contrat :\n\n" . $contractText;
            
            if ($isGoogle) {
                // Gemini API
                $baseUrl = $_ENV['GEMINI_BASE_URL'] ?? $_SERVER['GEMINI_BASE_URL'] ?? 'https://generativelanguage.googleapis.com/v1beta';
                $model = $_ENV['GEMINI_MODEL'] ?? $_SERVER['GEMINI_MODEL'] ?? 'gemini-2.0-flash';
                
                $url = rtrim($baseUrl, '/') . '/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($apiKey);

                $response = $client->request('POST', $url, [
                    'json' => [
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => $prompt]]]
                        ]
                    ]
                ]);
                $data = $response->toArray();
                $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? "Impossible de générer le résumé.";
            } else {
                // OpenAI API fallback
                $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                    'headers' => ['Authorization' => 'Bearer ' . $apiKey],
                    'json' => [
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            ['role' => 'system', 'content' => "Tu es un assistant IA qui aide les freelances à lire les contrats."],
                            ['role' => 'user', 'content' => $prompt]
                        ]
                    ]
                ]);
                $data = $response->toArray();
                $aiText = $data['choices'][0]['message']['content'] ?? "Impossible de générer le résumé.";
            }

            // Convert markdown to HTML: bold, bullets, newlines
            $aiHtml = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $aiText);
            $aiHtml = str_replace('* ', '• ', $aiHtml);
            $aiHtml = nl2br($aiHtml);

            return new \Symfony\Component\HttpFoundation\JsonResponse(['summary' => $aiHtml]);
        } catch (\Exception $e) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Erreur IA: ' . $e->getMessage()], 500);
        }
    }
}
