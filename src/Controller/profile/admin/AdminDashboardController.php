<?php

namespace App\Controller\profile\admin;

use App\Repository\users\user\UserRepository;
use App\Repository\users\client\ClientRepository;
use App\Repository\users\freelancer\FreelancerRepository;
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
        FreelancerRepository $freelancerRepo
    ): Response {
        if ($r = $this->requireAdmin($request)) return $r;

        $search = trim($request->query->get('search', ''));
        $role   = $request->query->get('role', 'All');
        $status = $request->query->get('status', 'All');
        $sort   = $request->query->get('sort', 'name_asc');

        $users = $userRepo->findAllUsers();

        // Search
        if ($search) {
            $users = array_filter($users, fn($u) =>
                str_contains(strtolower($u->getName()), strtolower($search)) ||
                str_contains(strtolower($u->getEmail()), strtolower($search))
            );
        }

        // Role filter
        if ($role !== 'All') {
            $users = array_filter($users, fn($u) => $u->getRole() === strtoupper($role));
        }

        // Status filter
        if ($status === 'Active') {
            $users = array_filter($users, fn($u) => $u->isActivated());
        } elseif ($status === 'Deactivated') {
            $users = array_filter($users, fn($u) => !$u->isActivated());
        }

        // Sort
        $users = array_values($users);
        usort($users, match ($sort) {
            'name_desc'  => fn($a, $b) => strcmp($b->getName(), $a->getName()),
            'email_asc'  => fn($a, $b) => strcmp($a->getEmail(), $b->getEmail()),
            'role'       => fn($a, $b) => strcmp($a->getRole(), $b->getRole()),
            'status'     => fn($a, $b) => $b->isActivated() <=> $a->isActivated(),
            default      => fn($a, $b) => strcmp($a->getName(), $b->getName()),
        });

        return $this->render('backOffice/manage-users.html.twig', [
            'users'      => $users,
            'search'     => $search,
            'role'       => $role,
            'status'     => $status,
            'sort'       => $sort,
            'adminName'  => $request->getSession()->get('user_name'),
        ]);
    }

    // ── VIEW USER DETAILS ──────────────────────────────────────────────

    #[Route('/users/{id}/view', name: 'admin_user_view', methods: ['GET'])]
    public function viewUser(
        int                  $id,
        Request              $request,
        UserRepository       $userRepo,
        ClientRepository     $clientRepo,
        FreelancerRepository $freelancerRepo
    ): Response {
        if ($r = $this->requireAdmin($request)) return $r;

        $user       = $userRepo->find($id);
        if (!$user) throw $this->createNotFoundException();

        $client     = $user->getRole() === 'CLIENT'     ? $clientRepo->findByUserId($id)     : null;
        $freelancer = $user->getRole() === 'FREELANCER' ? $freelancerRepo->findByUserId($id) : null;

        return $this->render('backOffice/user-detail.html.twig', [
            'user'       => $user,
            'client'     => $client,
            'freelancer' => $freelancer,
            'adminName'  => $request->getSession()->get('user_name'),
        ]);
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
        if ($user) { $em->remove($user); $em->flush(); }

        $this->addFlash('success', 'User deleted successfully.');
        return $this->redirectToRoute('admin_manage_users');
    }
}