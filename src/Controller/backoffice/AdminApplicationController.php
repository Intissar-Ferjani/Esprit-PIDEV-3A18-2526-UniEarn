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
        ApplicationRepository $applicationRepository,
        \App\Repository\project\ProjectRepository $projectRepository
    ): Response {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        // Handling search and sort
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'date');

        // Fetch applications filtering by search and sort
        $applications = $applicationRepository->findBySearchAndSort(null, $search, $sortBy, null);

        $allUniqueProjectIds = array_unique(array_map(fn($a) => $a->getProjectId(), $applications));
        $projectsList = $projectRepository->findByIds($allUniqueProjectIds);
        $projectTitles = [];
        foreach ($projectsList as $p) {
            $projectTitles[$p->getIdProject()] = $p->getTitle();
        }

        return $this->render('backoffice/application/index.html.twig', [
            'applications' => $applications,
            'projectTitles' => $projectTitles,
            'active' => 'applications'
        ]);
    }
}
