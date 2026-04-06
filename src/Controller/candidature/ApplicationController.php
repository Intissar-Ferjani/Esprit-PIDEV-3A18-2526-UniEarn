<?php

namespace App\Controller\candidature;

use App\Entity\candidature\Application;
use App\Form\candidature\ApplicationType;
use App\Repository\candidature\ApplicationRepository;
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

#[Route('/application')]
class ApplicationController extends AbstractController
{
    #[Route('/', name: 'app_application_index', methods: ['GET'])]
    public function index(
        Request $request, 
        ApplicationRepository $applicationRepository,
        FreelancerRepository $freelancerRepo,
        ClientRepository $clientRepo
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $client = $clientRepo->findByUserId($userId);
        
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'date');

        if ($freelancer) {
            $all = $applicationRepository->findBySearchAndSort($freelancer, $search, $sortBy);
        } elseif ($client) {
            $all = $applicationRepository->findBySearchAndSort(null, $search, $sortBy);
        } else {
            return $this->redirectToRoute('user_login');
        }

        $pending = array_filter($all, fn($a) => $a->getStatus()->value === 'PENDING');
        $treated = array_filter($all, fn($a) => $a->getStatus()->value !== 'PENDING');

        // For freelancers, look up which client owns each project
        $clientByProject = [];
        if ($freelancer && count($all) > 0) {
            $projectIds = array_unique(array_map(fn($a) => $a->getProjectId(), array_values($all)));
            $clientByProject = $applicationRepository->getClientInfoByProjectIds($projectIds);
        }

        return $this->render('candidature/application/index.html.twig', [
            'applicationsPending' => array_values($pending),
            'applicationsTreated' => array_values($treated),
            'clientByProject'     => $clientByProject,
            'freelancer' => $freelancer ?? null,
            'client'     => $client ?? null,
            'user'       => $freelancer ? $freelancer->getUser() : ($client ? $client->getUser() : null),
            'active'     => 'applications',
        ]);
    }

    #[Route('/new', name: 'app_application_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        FreelancerRepository $freelancerRepository
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepository->findByUserId($userId);
        if (!$freelancer) {
            $this->addFlash('error', 'Only freelancers can submit applications.');
            return $this->redirectToRoute('freelancer_dashboard');
        }

        $application = new Application();
        $application->setFreelancer($freelancer);
        
        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($application);
            $entityManager->flush();

            $this->addFlash('success', 'Application submitted successfully!');
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
        ClientRepository $clientRepo
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $freelancerRepo->findByUserId($userId);
        $client = $clientRepo->findByUserId($userId);

        return $this->render('candidature/application/show.html.twig', [
            'application' => $application,
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
        if ($this->isCsrfTokenValid('accept'.$application->getId(), $request->request->get('_token'))) {
            $application->setStatus(\App\Enum\ApplicationStatus::ACCEPTED);
            
            // Reject other applications for the same project
            $others = $appRepo->findBy(['projectId' => $application->getProjectId()]);
            foreach ($others as $other) {
                if ($other->getId() !== $application->getId()) {
                    $other->setStatus(\App\Enum\ApplicationStatus::REJECTED);
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
        if ($this->isCsrfTokenValid('reject'.$application->getId(), $request->request->get('_token'))) {
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
        if ($this->isCsrfTokenValid('delete'.$application->getId(), $request->request->get('_token'))) {
            $entityManager->remove($application);
            $entityManager->flush();
            $this->addFlash('success', 'Application deleted successfully.');
        }

        return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/pdf', name: 'app_application_pdf', methods: ['GET'])]
    public function generatePdf(Application $application): Response
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('candidature/application/pdf.html.twig', [
            'application' => $application
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="application-%s.pdf"', $application->getId()),
            ]
        );
    }
}
