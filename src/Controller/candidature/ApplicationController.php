<?php

namespace App\Controller\candidature;

use App\Entity\candidature\Application;
use App\Form\candidature\ApplicationType;
use App\Repository\candidature\ApplicationRepository;
use App\Repository\project\ProjectRepository;
use App\Repository\users\client\ClientRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\users\user\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Service\AiAnalysisService;
use App\Service\PdfService;
use App\Service\CurrencyService;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/application')]
class ApplicationController extends AbstractController
{
    #[Route('/', name: 'app_application_index', methods: ['GET'])]
    public function index(
        Request $request,
        ApplicationRepository $applicationRepository,
        FreelancerRepository $freelancerRepo,
        ClientRepository $clientRepo,
        ProjectRepository $projectRepo,
        PaginatorInterface $paginator
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId)
            return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $client = $clientRepo->findByUserId($userId);

        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'date');

        if ($freelancer) {
            $all = $applicationRepository->findBySearchAndSort($freelancer, $search, $sortBy);
        } elseif ($client) {
            // Get client's projects to filter applications
            $projects = $projectRepo->findBy(['client' => $client]);
            $projectIds = array_map(fn($p) => $p->getIdProject(), $projects);
            $all = $applicationRepository->findBySearchAndSort(null, $search, $sortBy, $projectIds);
        } else {
            return $this->redirectToRoute('user_login');
        }

        $pendingQuery = array_filter($all, fn($a) => $a->getStatus()->value === 'PENDING');
        $treatedQuery = array_filter($all, fn($a) => $a->getStatus()->value !== 'PENDING');

        // Fetch project titles for all applications in this view
        $allUniqueProjectIds = array_unique(array_filter(array_map(fn($a) => $a->getProject()?->getIdProject(), $all)));
        $projectsList = $projectRepo->findByIds($allUniqueProjectIds);
        $projectTitles = [];
        foreach ($projectsList as $p) {
            $projectTitles[$p->getIdProject()] = $p->getTitle();
        }

        $paginationPending = $paginator->paginate(
            array_values($pendingQuery),
            $request->query->getInt('page_pending', 1),
            5,
            ['pageParameterName' => 'page_pending']
        );

        $paginationTreated = $paginator->paginate(
            array_values($treatedQuery),
            $request->query->getInt('page_treated', 1),
            5,
            ['pageParameterName' => 'page_treated']
        );

        // For freelancers, look up which client owns each project
        $clientByProject = [];
        if ($freelancer && count($all) > 0) {
            $projectIds = array_unique(array_filter(array_map(fn($a) => $a->getProject()?->getIdProject(), array_values($all))));
            $clientByProject = $applicationRepository->getClientInfoByProjectIds($projectIds);
        }

        return $this->render('candidature/application/index.html.twig', [
            'applicationsPending' => $paginationPending,
            'applicationsTreated' => $paginationTreated,
            'clientByProject' => $clientByProject,
            'projectTitles' => $projectTitles,
            'freelancer' => $freelancer ?? null,
            'client' => $client ?? null,
            'user' => $freelancer ? $freelancer->getUser() : ($client ? $client->getUser() : null),
            'active' => 'applications',
        ]);
    }

    #[Route('/new', name: 'app_application_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        FreelancerRepository $freelancerRepository,
        ApplicationRepository $applicationRepo,
        AiAnalysisService $aiService
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId)
            return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepository->findByUserId($userId);
        if (!$freelancer) {
            $this->addFlash('error', 'Only freelancers can submit applications.');
            return $this->redirectToRoute('freelancer_dashboard');
        }

        $application = new Application();
        $application->setFreelancer($freelancer);

        $projectId = (int) $request->query->get('projectId', 0);
        if ($projectId > 0) {
            $project = $entityManager->getRepository(\App\Entity\project\Project::class)->find($projectId);
            if ($project) {
                $application->setProject($project);
            }
        }

        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Metier Avancé 1: Application Limit (3 pending max)
            $existingPending = $applicationRepo->findBy([
                'freelancer' => $freelancer,
                'status' => \App\Enum\ApplicationStatus::PENDING
            ]);
            if (count($existingPending) >= 3) {
                $this->addFlash('error', 'You already have 3 pending applications. Please wait for them to be treated before applying again.');
                return $this->redirectToRoute('app_application_index');
            }

            // AI Integration: Cover Letter Analysis
            $aiResults = $aiService->analyzeCoverLetter($application->getCoverLetter());
            $application->setAiAnalysis(json_encode($aiResults));

            // Metier Avancé 2: Auto-rejection if AI score is too low (Threshold: 0.1)
            if ($aiResults['score'] < 0.1) {
                $application->setStatus(\App\Enum\ApplicationStatus::REJECTED);
                $entityManager->persist($application);
                $entityManager->flush();
                $this->addFlash('warning', 'Your application was automatically rejected due to a low-quality cover letter (AI Score: ' . round($aiResults['score'] * 100) . '%). Please improve it and try again.');
                return $this->redirectToRoute('app_application_index');
            }

            // Metier Avancé 3: Budget Range Validation (Metier + Repository logic)
            $projectId = $application->getProject()?->getIdProject();
            $projectBudget = $projectId ? $applicationRepo->getProjectBudget($projectId) : null;
            if ($projectBudget && $application->getProposedBudget() > $projectBudget * 1.5) {
                $this->addFlash('warning', 'Note: Your proposed budget is significantly higher than the client\'s initial budget for this project.');
            }

            // Advanced Business Logic: Compatibility Score
            $score = 0.5; // Default
            if ($application->getProposedBudget() > 0) {
                $budgetFactor = $projectBudget ? min(1.0, $projectBudget / $application->getProposedBudget()) : 0.5;
                $durationFactor = max(0.2, 1.0 - ($application->getEstimatedDuration() / 100));
                $score = ($budgetFactor * 0.6) + ($durationFactor * 0.4);
            }
            $application->setCompatibilityScore(round($score * 100, 2));

            $entityManager->persist($application);
            $entityManager->flush();

            $this->addFlash('success', 'Application submitted! AI cover letter score: ' . round($aiResults['score'] * 100) . '%.');
            return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('candidature/application/new.html.twig', [
            'application' => $application,
            'form' => $form,
            'freelancer' => $freelancer,
            'user' => $freelancer->getUser(),
            'active' => 'applications',
        ]);
    }

    #[Route('/{id}', name: 'app_application_show', methods: ['GET'])]
    public function show(
        Request $request,
        Application $application,
        FreelancerRepository $freelancerRepo,
        ClientRepository $clientRepo,
        ProjectRepository $projectRepo,
        CurrencyService $currencyService
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId)
            return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $client = $clientRepo->findByUserId($userId);

        $project = $application->getProject();

        $converted = [];
        if ($application->getProposedBudget() > 0) {
            $rates = $currencyService->convertFromTnd($application->getProposedBudget());
            $converted = [
                'USD' => $rates['USD'] ?? null,
                'EUR' => $rates['EUR'] ?? null
            ];
        }

        return $this->render('candidature/application/show.html.twig', [
            'application' => $application,
            'projectTitle' => $project ? $project->getTitle() : ('#' . ($application->getProject()?->getIdProject() ?? '?')),
            'aiData' => json_decode($application->getAiAnalysis(), true),
            'convertedBudgets' => $converted,
            'freelancer' => $freelancer,
            'client' => $client,
            'user' => $freelancer ? $freelancer->getUser() : ($client ? $client->getUser() : null),
            'active' => 'applications',
        ]);
    }

    #[Route('/{id}/accept', name: 'app_application_accept', methods: ['POST'])]
    public function accept(
        Request $request,
        Application $application,
        EntityManagerInterface $entityManager,
        ApplicationRepository $appRepo
    ): Response {
        if ($this->isCsrfTokenValid('accept' . $application->getId(), $request->request->get('_token'))) {
            $application->setStatus(\App\Enum\ApplicationStatus::ACCEPTED);

            // Reject other applications for the same project
            $projectId = $application->getProject()?->getIdProject();
            if ($projectId) {
                $others = $appRepo->findByProjectId($projectId);
                foreach ($others as $other) {
                    if ($other->getId() !== $application->getId()) {
                        $other->setStatus(\App\Enum\ApplicationStatus::REJECTED);
                    }
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Application accepted successfully.');
        }

        return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reject', name: 'app_application_reject', methods: ['POST'])]
    public function reject(
        Request $request,
        Application $application,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('reject' . $application->getId(), $request->request->get('_token'))) {
            $application->setStatus(\App\Enum\ApplicationStatus::REJECTED);
            $entityManager->flush();
            $this->addFlash('success', 'Application rejected.');
        }

        return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete', name: 'app_application_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Application $application,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $application->getId(), $request->request->get('_token'))) {
            $entityManager->remove($application);
            $entityManager->flush();
            $this->addFlash('success', 'Application deleted successfully.');
        }

        return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/pdf', name: 'app_application_pdf', methods: ['GET'])]
    public function generatePdf(Application $application, PdfService $pdfService, ProjectRepository $projectRepo): Response
    {
        $aiData = null;
        if ($application->getAiAnalysis()) {
            $aiData = json_decode($application->getAiAnalysis(), true);
        }

        $project = $application->getProject();

        $binary = $pdfService->generateBinaryPdf('candidature/application/pdf.html.twig', [
            'application' => $application,
            'projectTitle' => $project ? $project->getTitle() : ('#' . ($application->getProject()?->getIdProject() ?? '?')),
            'aiData' => $aiData
        ]);

        return new Response(
            $binary,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="application-%s.pdf"', $application->getId()),
            ]
        );
    }
}