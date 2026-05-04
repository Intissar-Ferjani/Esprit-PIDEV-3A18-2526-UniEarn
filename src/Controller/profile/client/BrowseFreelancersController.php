<?php

namespace App\Controller\profile\client;

use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\users\client\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\project\ProjectRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/client')]
class BrowseFreelancersController extends AbstractController
{
    #[Route('/freelancers', name: 'client_browse_freelancers', methods: ['GET'])]
    public function browse(
        Request              $request,
        FreelancerRepository $freelancerRepo,
        ClientRepository     $clientRepo,
        \Knp\Component\Pager\PaginatorInterface $paginator,
        ProjectRepository    $projectRepo
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $client = $clientRepo->findByUserId($userId);
        if (!$client) return $this->redirectToRoute('user_login');

        // Filters from query string
        $search       = trim($request->query->get('search', ''));
        $verification = $request->query->get('verification', 'All');
        $minRating    = $request->query->get('rating', 'Any');
        $sortBy       = $request->query->get('sortBy', 'rating_high');
        $limit        = $request->query->getInt('limit', 5);

        // Use Repository to get QueryBuilder
        $queryBuilder = $freelancerRepo->getSearchQueryBuilder($search, $verification, $minRating, $sortBy);

        // Paginate
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $limit
        );

        $template = $request->query->get('ajax') 
            ? 'frontOffice/client/profile/_freelancer_results.html.twig'
            : 'frontOffice/client/profile/browse-freelancers.html.twig';

        $projects = $projectRepo->findByClient($client->getIdClient());
        $activeProjects = array_filter($projects, fn($p) => in_array($p->getStatus()->value, ['TODO', 'Review', 'IN_PROGRESS']));

        return $this->render($template, [
            'pagination'   => $pagination,
            'client'       => $client,
            'user'         => $client->getUser(),
            'search'       => $search,
            'verification' => $verification,
            'minRating'    => $minRating,
            'sortBy'       => $sortBy,
            'limit'        => $limit,
            'count'        => $pagination->getTotalItemCount(),
            'activeProjects' => $activeProjects,
        ]);
    }

    #[Route('/freelancers/recommended', name: 'client_freelancers_recommended', methods: ['GET'])]
    public function recommended(
        Request $request,
        FreelancerRepository $freelancerRepo,
        ClientRepository $clientRepo,
        ProjectRepository $projectRepo,
        HttpClientInterface $clientHttp
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return new Response('Unauthorized', 401);

        $client = $clientRepo->findByUserId($userId);
        if (!$client) return new Response('Unauthorized', 401);

        // Fetch client projects
        $projects = $projectRepo->findByClient($client->getIdClient());
        $activeProjects = array_filter($projects, fn($p) => in_array($p->getStatus()->value, ['TODO', 'Review', 'IN_PROGRESS']));

        // Filter client projects
        $projectId = $request->query->get('projectId');
        if ($projectId && $projectId !== 'All') {
            $activeProjects = array_filter($activeProjects, fn($p) => $p->getIdProject() == $projectId);
        }

        if (empty($activeProjects)) {
            return $this->render('frontOffice/client/profile/_recommended_results.html.twig', [
                'error' => 'You do not have any active projects matching this filter.',
                'recommendations' => []
            ]);
        }

        // Fetch available freelancers based on filters
        $minRating = $request->query->get('minRating', 'Any');
        $maxPrice = $request->query->get('maxPrice', 'Any');

        $qb = $freelancerRepo->createQueryBuilder('f')
            ->where('f.status = :status')
            ->setParameter('status', 'available');

        if ($minRating !== 'Any') {
            $ratingVal = (float) str_replace('+', '', $minRating);
            $qb->andWhere('f.rating >= :rating')
               ->setParameter('rating', $ratingVal);
        }

        if ($maxPrice !== 'Any') {
            $qb->andWhere('f.pricePerHour <= :price')
               ->setParameter('price', (float)$maxPrice);
        }

        $qb->orderBy('f.rating', 'DESC')->setMaxResults(40);
        $freelancers = $qb->getQuery()->getResult();
        
        if (empty($freelancers)) {
            return $this->render('frontOffice/client/profile/_recommended_results.html.twig', [
                'error' => 'No freelancers match your rating/price criteria.',
                'recommendations' => []
            ]);
        }
        
        $freelancersData = [];
        foreach ($freelancers as $f) {
            $freelancersData[] = [
                'id' => $f->getIdFreelancer(),
                'skills' => $f->getSkills(),
                'bio' => $f->getBio()
            ];
        }

        $projectsData = [];
        foreach ($activeProjects as $p) {
            $projectsData[] = [
                'title' => $p->getTitle(),
                'description' => $p->getDescription()
            ];
        }

        $prompt = "You are an expert AI recruiter matching freelancers to client projects.\n\n" .
                  "Client Projects:\n" . json_encode($projectsData) . "\n\n" .
                  "Available Freelancers:\n" . json_encode($freelancersData) . "\n\n" .
                  "Analyze the skills and bios of the freelancers and see which ones best match the client's projects. " .
                  "Return a JSON array containing EXACTLY the top 5 recommended freelancer IDs, the exact title of the specific project they are best suited for, the suggested developer role (e.g., 'Frontend Developer', 'Backend Developer', 'Full Stack', 'Data Scientist', etc.), and a short (1-2 sentence) reason for each explaining why they are a good fit. " .
                  "Format MUST be strict JSON: [{\"id\": 123, \"projectTitle\": \"Title of the Project\", \"role\": \"Backend Developer\", \"reason\": \"Because they have PHP skills...\"}] . Do not include markdown code blocks or any other text.";

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? $_ENV['OPENAI_API_KEY'] ?? '';
        
        if (!$apiKey) {
            // Simulated fallback for demo
            sleep(1);
            $simulated = [];
            foreach (array_slice($freelancers, 0, 5) as $f) {
                $simulated[] = [
                    'freelancer' => $f,
                    'projectTitle' => reset($activeProjects)->getTitle(),
                    'role' => 'Full Stack Developer',
                    'reason' => "Simulated match based on strong general rating. (Add GEMINI_API_KEY to .env for real AI matching)"
                ];
            }
            return $this->render('frontOffice/client/profile/_recommended_results.html.twig', [
                'recommendations' => $simulated,
                'error' => null
            ]);
        }

        try {
            $baseUrl = $_ENV['GEMINI_BASE_URL'] ?? 'https://generativelanguage.googleapis.com/v1beta';
            $model = $_ENV['GEMINI_MODEL'] ?? 'gemini-flash-latest';
            $url = rtrim($baseUrl, '/') . '/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($apiKey);

            $response = $clientHttp->request('POST', $url, [
                'verify_peer' => false,
                'json' => [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]]
                    ]
                ]
            ]);

            $data = $response->toArray();
            $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? "[]";
            
            // Clean markdown blocks if AI ignored instructions
            $aiText = str_replace(['```json', '```'], '', $aiText);
            $aiText = trim($aiText);
            
            $aiMatches = json_decode($aiText, true);

            $recommendations = [];
            if (is_array($aiMatches)) {
                foreach ($aiMatches as $match) {
                    if (isset($match['id']) && isset($match['reason'])) {
                        $freelancer = $freelancerRepo->find($match['id']);
                        if ($freelancer) {
                            $recommendations[] = [
                                'freelancer' => $freelancer,
                                'projectTitle' => $match['projectTitle'] ?? 'A project',
                                'role' => $match['role'] ?? 'Developer',
                                'reason' => $match['reason']
                            ];
                        }
                    }
                }
            }

            return $this->render('frontOffice/client/profile/_recommended_results.html.twig', [
                'recommendations' => $recommendations,
                'error' => empty($recommendations) ? "AI could not find strong matches at this time." : null
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, '429')) {
                $errorMessage = "The AI service is currently busy (Rate Limit Exceeded). Please wait a few seconds and try again.";
            } else {
                $errorMessage = 'AI Service Error: ' . $errorMessage;
            }

            return $this->render('frontOffice/client/profile/_recommended_results.html.twig', [
                'recommendations' => [],
                'error' => $errorMessage
            ]);
        }
    }
}