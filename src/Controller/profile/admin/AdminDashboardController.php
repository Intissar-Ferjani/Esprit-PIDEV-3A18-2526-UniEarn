<?php

namespace App\Controller\profile\admin;

use App\Entity\contract\Contract;
use App\Repository\users\user\UserRepository;
use App\Repository\users\client\ClientRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\project\ProjectRepository;
use App\Repository\task\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    private function requireAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    // ── MAIN DASHBOARD ─────────────────────────────────────────────────

    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, UserRepository $userRepo): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $allUsers = $userRepo->findAllUsers();

        $stats = [
            'total'       => count($allUsers),
            'clients'     => count(array_filter($allUsers, fn($u) => $u->getRole() === 'CLIENT')),
            'freelancers' => count(array_filter($allUsers, fn($u) => $u->getRole() === 'FREELANCER')),
            'active'      => count(array_filter($allUsers, fn($u) => $u->isActivated())),
        ];
        $stats['activePercent'] = $stats['total'] > 0
            ? round($stats['active'] * 100 / $stats['total'], 1)
            : 0;

        return $this->render('backOffice/dashboard.html.twig', [
            'adminName' => $request->getSession()->get('user_name'),
            'stats'     => $stats,
        ]);
    }

    // ── MANAGE USERS ───────────────────────────────────────────────────

    #[Route('/users', name: 'admin_manage_users', methods: ['GET'])]
    public function manageUsers(
        Request              $request,
        UserRepository       $userRepo,
        ClientRepository     $clientRepo,
        FreelancerRepository $freelancerRepo,
        \Knp\Component\Pager\PaginatorInterface $paginator
    ): Response {
        if ($r = $this->requireAdmin($request)) return $r;

        $userId = $request->query->get('userId') ? $request->query->getInt('userId') : null;
        $search = trim($request->query->get('search', ''));
        $role   = $request->query->get('role', 'All');
        $status = $request->query->get('status', 'All');
        $sortBy = $request->query->get('sortBy', 'name_asc');
        $limit  = $request->query->getInt('limit', 10);

        $queryBuilder = $userRepo->getAdminSearchQueryBuilder($search, $userId, $role, $status, $sortBy);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $limit
        );

        $template = $request->query->get('ajax')
            ? 'backOffice/admin/_user_table_results.html.twig'
            : 'backOffice/admin/manage-users.html.twig';

        return $this->render($template, [
            'pagination' => $pagination,
            'users'      => $pagination, // Map to users for compatibility
            'userId'     => $userId,
            'search'     => $search,
            'role'       => $role,
            'status'     => $status,
            'sortBy'     => $sortBy,
            'limit'      => $limit,
            'adminName'  => $request->getSession()->get('user_name'),
        ]);
    }

    // ── VIEW USER DETAILS ──────────────────────────────────────────────

    #[Route('/users/{id}/view', name: 'admin_user_view', methods: ['GET'])]
    public function viewUser(
        int                     $id,
        Request                 $request,
        UserRepository          $userRepo,
        ClientRepository        $clientRepo,
        FreelancerRepository    $freelancerRepo,
        \App\Repository\users\freelancer\PortfolioRepository     $portfolioRepo,
        \App\Repository\users\freelancer\PortfolioItemRepository $itemRepo
    ): Response {
        if ($r = $this->requireAdmin($request)) return $r;

        $user = $userRepo->find($id);
        if (!$user) throw $this->createNotFoundException();

        // ── CLIENT ──
        if ($user->getRole() === 'CLIENT') {
            $client = $clientRepo->findByUserId($id);

            if (!$client) {
                $this->addFlash('error', 'Client profile data not found for this user.');
                return $this->redirectToRoute('admin_manage_users');
            }

            return $this->render('frontOffice/client/profile/dashboard.html.twig', [
                'client'         => $client,
                'user'           => $user,
                'viewerRole'     => 'ADMIN',
                'backUrl'        => $this->generateUrl('admin_manage_users'),
                'sidebarInclude' => 'frontOffice/user/profile/_sidebar.html.twig',
                'sidebarActive'  => 'users',
            ]);
        }

        // ── FREELANCER ──
        if ($user->getRole() === 'FREELANCER') {
            $freelancer = $freelancerRepo->findByUserId($id);

            // Guard: freelancer record missing
            if (!$freelancer) {
                $this->addFlash('error', 'Freelancer profile data not found for this user.');
                return $this->redirectToRoute('admin_manage_users');
            }

            $portfolio = $portfolioRepo->findByFreelancerId($freelancer->getIdFreelancer());
            $items     = $portfolio ? $itemRepo->findByPortfolioId($portfolio->getIdPortfolio()) : [];

            return $this->render('frontOffice/freelancer/profile/dashboard.html.twig', [
                'freelancer'     => $freelancer,
                'user'           => $user,
                'viewerRole'     => 'ADMIN',
                'backUrl'        => $this->generateUrl('admin_manage_users'),
                'portfolio'      => $portfolio,
                'items'          => $items,
                'sidebarInclude' => 'frontOffice/user/profile/_sidebar.html.twig',
                'sidebarActive'  => 'users',
            ]);
        }

        // ── ADMIN user — just redirect back ──
        $this->addFlash('error', 'Cannot view admin profiles.');
        return $this->redirectToRoute('admin_manage_users');
    }

    // ── TOGGLE ACTIVATE / DEACTIVATE ──────────────────────────────────

    #[Route('/users/{id}/toggle', name: 'admin_toggle_user', methods: ['POST'])]
    public function toggleUser(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $user = $repo->find($id);
        if (!$user) throw $this->createNotFoundException();

        $user->setActivated(!$user->isActivated());
        $em->flush();

        $action = $user->isActivated() ? 'activated' : 'deactivated';
        $this->addFlash('success', "{$user->getName()} has been {$action}.");

        return $this->redirectToRoute('admin_manage_users');
    }

    // ── DELETE USER ────────────────────────────────────────────────────

    #[Route('/users/{id}/delete', name: 'admin_delete_user', methods: ['POST'])]
    public function deleteUser(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $user = $repo->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_manage_users');
        }

        // Check if this user has linked contracts (as client or freelancer)
        $contractRepo = $em->getRepository(Contract::class);
        $contractCount = 0;

        $client = $em->getRepository(\App\Entity\users\client\Client::class)->findOneBy(['user' => $user]);
        if ($client) {
            $contractCount += $contractRepo->count(['client' => $client]);
        }

        $freelancer = $em->getRepository(\App\Entity\users\freelancer\Freelancer::class)->findOneBy(['user' => $user]);
        if ($freelancer) {
            $contractCount += $contractRepo->count(['freelancer' => $freelancer]);
        }

        if ($contractCount > 0) {
            $this->addFlash('error', "Cannot delete this user: $contractCount contract(s) are still linked to them.");
            return $this->redirectToRoute('admin_manage_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User deleted successfully.');
        return $this->redirectToRoute('admin_manage_users');
    }

    // ── MANAGE PROJECTS + TASKS (READ-ONLY) ───────────────────────────
    #[Route('/projects', name: 'admin_manage_projects', methods: ['GET'])]
    public function manageProjects(
        Request $request,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository
    ): Response {
        if ($r = $this->requireAdmin($request)) return $r;

        $search = trim((string) $request->query->get('search', ''));
        $status = (string) $request->query->get('status', 'All');
        $hasTasks = (string) $request->query->get('hasTasks', 'All');
        $sort = (string) $request->query->get('sort', 'newest');

        $projects = $projectRepository->findAll();
        $tasksByProject = [];
        foreach ($projects as $project) {
            $tasksByProject[$project->getIdProject()] = $taskRepository->findBy(['project' => $project], ['idTask' => 'DESC']);
        }

        if ($search !== '') {
            $projects = array_filter($projects, static function ($project) use ($search): bool {
                $needle = strtolower($search);
                return str_contains(strtolower($project->getTitle() ?? ''), $needle)
                    || str_contains(strtolower($project->getDescription() ?? ''), $needle);
            });
        }

        if ($status !== 'All') {
            $projects = array_filter($projects, static fn ($project): bool =>
                ($project->getStatus()->value ?? '') === $status
            );
        }

        if ($hasTasks === 'WithTasks') {
            $projects = array_filter($projects, fn ($project): bool => count($tasksByProject[$project->getIdProject()] ?? []) > 0);
        } elseif ($hasTasks === 'WithoutTasks') {
            $projects = array_filter($projects, fn ($project): bool => count($tasksByProject[$project->getIdProject()] ?? []) === 0);
        }

        $projects = array_values($projects);
        usort($projects, match ($sort) {
            'title_asc' => fn ($a, $b) => strcmp((string) $a->getTitle(), (string) $b->getTitle()),
            'title_desc' => fn ($a, $b) => strcmp((string) $b->getTitle(), (string) $a->getTitle()),
            'budget_desc' => fn ($a, $b) => ($b->getBudget() ?? 0) <=> ($a->getBudget() ?? 0),
            default => fn ($a, $b) => ($b->getIdProject() ?? 0) <=> ($a->getIdProject() ?? 0),
        });

        return $this->render('backOffice/project/manage-projects.html.twig', [
            'projects' => $projects,
            'tasksByProject' => $tasksByProject,
            'search' => $search,
            'status' => $status,
            'hasTasks' => $hasTasks,
            'sort' => $sort,
            'adminName' => $request->getSession()->get('user_name'),
        ]);
    }
}