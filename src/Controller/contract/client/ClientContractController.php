<?php

namespace App\Controller\contract\client;

use App\Entity\contract\Contract;
use App\Form\contract\ContractFormType;
use App\Repository\contract\ContractRepository;
use App\Repository\contract\ContractTemplateRepository;
use App\Repository\users\client\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/client/contracts')]
class ClientContractController extends AbstractController
{
    private function requireClient(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'CLIENT') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    // ── LIST CLIENT CONTRACTS ───────────────────────────────────────────

    #[Route('/', name: 'client_contract_index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $repo, ClientRepository $clientRepo, PaginatorInterface $paginator): Response
    {
        if ($r = $this->requireClient($request))
            return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        if (!$client)
            throw $this->createNotFoundException('Client not found.');

        $status = $request->query->get('status');
        $validStatuses = ['pending', 'signed'];
        if ($status && !in_array($status, $validStatuses, true)) {
            $status = null;
        }

        $contractsQuery = $repo->findByClientId($client->getIdClient(), $status);
        $pagination = $paginator->paginate(
            $contractsQuery,
            $request->query->getInt('page', 1), /* page number */
            5 /* limit per page */
        );

        return $this->render('frontOffice/client/contract/list-contracts.html.twig', [
            'contracts' => $pagination,
            'currentStatus' => $status,
        ]);
    }

    // ── CREATE CONTRACT FROM TEMPLATE ────────────────────────────────────

    #[Route('/new/{templateId}', name: 'client_contract_new', methods: ['GET', 'POST'])]
    public function new(int $templateId, Request $request, ContractTemplateRepository $templateRepo, ClientRepository $clientRepo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireClient($request))
            return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        if (!$client)
            throw $this->createNotFoundException('Client not found.');

        $template = $templateRepo->find($templateId);
        if (!$template)
            throw $this->createNotFoundException('Template not found.');

        $contract = new Contract();
        $contract->setTemplate($template);
        $contract->setClient($client);
        $contract->setContent($template->getContent());
        $contract->setTitle($template->getTitle());

        $form = $this->createForm(ContractFormType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract->setStatus('pending');
            $em->persist($contract);
            $em->flush();
            $this->addFlash('success', 'Contract created successfully.');
            return $this->redirectToRoute('client_contract_index');
        }

        return $this->render('frontOffice/client/contract/new-contract.html.twig', [
            'form' => $form->createView(),
            'template' => $template,
        ]);
    }

    // ── CHOOSE TEMPLATE ─────────────────────────────────────────────────

    #[Route('/templates', name: 'client_contract_templates', methods: ['GET'])]
    public function templates(Request $request, ContractTemplateRepository $repo): Response
    {
        if ($r = $this->requireClient($request))
            return $r;

        return $this->render('frontOffice/client/contract/choose-template.html.twig', [
            'templates' => $repo->findAllOrderedByDate(),
        ]);
    }

    // ── VIEW CONTRACT ───────────────────────────────────────────────────

    #[Route('/{id}', name: 'client_contract_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request, ContractRepository $repo, ClientRepository $clientRepo): Response
    {
        if ($r = $this->requireClient($request))
            return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getClient()->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        return $this->render('frontOffice/client/contract/show-contract.html.twig', [
            'contract' => $contract,
        ]);
    }

    // ── SIGN CONTRACT (CLIENT) ──────────────────────────────────────────

    #[Route('/{id}/sign', name: 'client_contract_sign', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sign(int $id, Request $request, ContractRepository $repo, ClientRepository $clientRepo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireClient($request))
            return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getClient()->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        $signatureData = $request->request->get('signature');
        if (!$signatureData || !str_starts_with($signatureData, 'data:image/png;base64,')) {
            $this->addFlash('error', 'Please draw your signature before submitting.');
            return $this->redirectToRoute('client_contract_show', ['id' => $id]);
        }

        $contract->setClientSignature(new \DateTime());
        $contract->setClientSignatureImage($signatureData);
        $contract->setUpdatedAt(new \DateTime());

        if ($contract->getFreelancerSignature() !== null) {
            $contract->setStatus('signed');
        }

        $em->flush();
        $this->addFlash('success', 'You have signed the contract.');

        return $this->redirectToRoute('client_contract_show', ['id' => $id]);
    }

    // ── EXPORT PDF ──────────────────────────────────────────────────────

    #[Route('/{id}/pdf', name: 'client_contract_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(int $id, Request $request, ContractRepository $repo, ClientRepository $clientRepo): Response
    {
        if ($r = $this->requireClient($request))
            return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getClient()->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        if (!$contract->isSigned()) {
            $this->addFlash('error', 'Contract must be signed by both parties before exporting.');
            return $this->redirectToRoute('client_contract_show', ['id' => $id]);
        }

        return $this->render('frontOffice/contract/pdf-contract.html.twig', [
            'contract' => $contract,
        ]);
    }

    // ── AI SUMMARY ──────────────────────────────────────────────────────

    #[Route('/{id}/ai-summary', name: 'client_contract_ai_summary', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function aiSummary(int $id, Request $request, ContractRepository $repo, ClientRepository $clientRepo, \Symfony\Contracts\HttpClient\HttpClientInterface $clientHttp): \Symfony\Component\HttpFoundation\JsonResponse
    {
        if ($r = $this->requireClient($request))
            return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Unauthorized'], 401);

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getClient()->getIdClient() !== $client->getIdClient()) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Contract not found.'], 404);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? $_ENV['OPENAI_API_KEY'] ?? '';

        if (!$apiKey) {
            // Simulation pour la démonstration (quand pas de clé API)
            sleep(2);
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'summary' => "<ul><li style='margin-bottom:8px'><strong>💰 Budget :</strong> Le montant total que vous paierez est sécurisé à $" . $contract->getAmount() . ".</li><li style='margin-bottom:8px'><strong>📅 Échéance :</strong> Le prestataire s'engage à livrer avant le " . $contract->getEndDate()->format('d/m/Y') . ".</li><li style='margin-bottom:8px'><strong>ℹ️ Recommandation IA :</strong> Payez en séquestre dès la signature pour démarrer le projet. <br><em style='font-size:11px;color:#0ea5e9;'>(Mode simulation : ajoutez GEMINI_API_KEY dans votre fichier .env pour activer la vraie analyse !)</em></li></ul>",
            ]);
        }

        try {
            $isGoogle = str_starts_with($apiKey, 'AIza'); // Google API Keys start with AIza

            // Build a rich prompt with ALL contract metadata so the AI always has data
            $contractText = "Titre du contrat : " . ($contract->getTitle() ?? 'Non spécifié') . "\n";
            $contractText .= "Montant : $" . ($contract->getAmount() ?? '0') . "\n";
            $contractText .= "Date de début : " . ($contract->getStartDate() ? $contract->getStartDate()->format('d/m/Y') : 'Non spécifiée') . "\n";
            $contractText .= "Date de fin : " . ($contract->getEndDate() ? $contract->getEndDate()->format('d/m/Y') : 'Non spécifiée') . "\n";
            $contractText .= "Freelancer : " . ($contract->getFreelancer() ? $contract->getFreelancer()->getName() : 'Non spécifié') . "\n";
            $contractText .= "Statut : " . $contract->getStatus() . "\n";
            if ($contract->getContent()) {
                $contractText .= "\nContenu du contrat :\n" . $contract->getContent();
            }

            $prompt = "Agissez comme un avocat expert conseil d'un client. Résumez ce contrat en français en 3 puces courtes et claires. Structurez votre réponse ainsi :\n1. Aspect financier (montant, modalités)\n2. Calendrier et délais (dates, durée)\n3. Points de vigilance critiques\n\nVoici les informations du contrat :\n\n" . $contractText;

            if ($isGoogle) {
                // Gemini API
                $baseUrl = $_ENV['GEMINI_BASE_URL'] ?? $_SERVER['GEMINI_BASE_URL'] ?? 'https://generativelanguage.googleapis.com/v1beta';
                $model = $_ENV['GEMINI_MODEL'] ?? $_SERVER['GEMINI_MODEL'] ?? 'gemini-2.0-flash';

                $url = rtrim($baseUrl, '/') . '/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($apiKey);

                $response = $clientHttp->request('POST', $url, [
                    'json' => [
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => "Agissez comme un avocat expert conseil d'un client. Résumez ce contrat en français en 3 puces courtes et claires. Mettez en évidence le budget, les délais, et les points de vigilance : \n\n" . $contract->getContent()]]]
                        ]
                    ]
                ]);
                $response = $clientHttp->request('POST', 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $apiKey, [
                    'verify_peer' => false,
                    'json' => [
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => "Agissez comme un avocat expert conseil d'un client. Résumez ce contrat en français en 3 puces courtes et claires. Mettez en évidence le budget, les délais, et les points de vigilance : \n\n" . $contract->getContent()]]]
                        ]
                    ]
                ]);
                $data = $response->toArray();
                $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? "Impossible de générer le résumé.";
            } else {
                // OpenAI API fallback
                $response = $clientHttp->request('POST', 'https://api.openai.com/v1/chat/completions', [
                    'verify_peer' => false,
                    'headers' => ['Authorization' => 'Bearer ' . $apiKey],
                    'json' => [
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            ['role' => 'system', 'content' => "Tu es un avocat expert conseil. Ton rôle est de résumer des contrats de manière claire et concise."],
                            ['role' => 'user', 'content' => "Résume ce contrat en français en 3 puces courtes et claires. Mettez en évidence le budget, les délais, et les points de vigilance : \n\n" . $contract->getContent()]
                        ],
                        'temperature' => 0.3
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