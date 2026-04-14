<?php

namespace App\Controller\backoffice;

use App\Repository\candidature\ApplicationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/application')]
class AdminApplicationController extends AbstractController
{
    #[Route('/', name: 'admin_application_index', methods: ['GET'])]
    public function index(
        Request $request,
        ApplicationRepository $applicationRepository
    ): Response {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        // Fetch all applications
        $applications = $applicationRepository->findAll();

        return $this->render('backoffice/application/index.html.twig', [
            'applications' => $applications,
            'active' => 'applications'
        ]);
    }
}
