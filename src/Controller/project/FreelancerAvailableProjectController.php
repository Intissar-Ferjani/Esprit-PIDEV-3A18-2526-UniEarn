<?php

namespace App\Controller\project;

use App\Repository\candidature\ApplicationRepository;
use App\Repository\project\ProjectRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/freelancer/projects')]
final class FreelancerAvailableProjectController extends AbstractController
{
    #[Route('/', name: 'freelancer_available_projects', methods: ['GET'])]
    public function index(
        Request $request,
        ProjectRepository $projectRepository,
        FreelancerRepository $freelancerRepository,
        ApplicationRepository $applicationRepository
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $freelancer = $freelancerRepository->findByUserId($userId);
        if (!$freelancer) {
            return $this->redirectToRoute('freelancer_dashboard');
        }

        $projects = $projectRepository->findAvailableProjects();
        $search = trim((string) $request->query->get('search', ''));
        if ($search !== '') {
            $projects = array_values(array_filter($projects, static fn ($project): bool =>
                str_contains(strtolower($project->getTitle() ?? ''), strtolower($search)) ||
                str_contains(strtolower($project->getDescription() ?? ''), strtolower($search))
            ));
        }

        $appliedIds = $applicationRepository->findAppliedProjectIdsForFreelancer($freelancer->getIdFreelancer());

        return $this->render('frontOffice/freelancer/project/available_projects.html.twig', [
            'projects' => $projects,
            'applied_project_ids' => $appliedIds,
            'active' => 'projects',
        ]);
    }
}
