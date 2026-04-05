<?php

namespace App\Controller\auth\user;

use App\Repository\users\user\UserRepository;
use App\Repository\users\client\ClientRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    private function requireAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    // ── LIST ───────────────────────────────────────────────────────────

    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $repo): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        return $this->render('backOffice/user/list-users.html.twig', [
            'users' => $repo->findAllUsers(),
        ]);
    }

    // ── TOGGLE ACTIVATE / DEACTIVATE ──────────────────────────────────

    #[Route('/{id}/toggle', name: 'admin_user_toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $user = $repo->find($id);
        if (!$user) throw $this->createNotFoundException('User not found.');

        $user->setActivated(!$user->isActivated());
        $em->flush();

        $status = $user->isActivated() ? 'reactivated' : 'deactivated';
        $this->addFlash('success', "User {$user->getName()} has been {$status}.");

        return $this->redirectToRoute('admin_user_index');
    }

    // ── DELETE ─────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $user = $repo->find($id);
        if (!$user) throw $this->createNotFoundException('User not found.');

        $em->remove($user);
        $em->flush();
        $this->addFlash('success', 'User deleted successfully.');

        return $this->redirectToRoute('admin_user_index');
    }

    // ── VIEW DETAILS ───────────────────────────────────────────────────

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(int $id, Request $request, UserRepository $repo, ClientRepository $clientRepo, FreelancerRepository $freelancerRepo): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $user = $repo->find($id);
        if (!$user) throw $this->createNotFoundException('User not found.');

        $client     = $user->getRole() === 'CLIENT'     ? $clientRepo->findByUserId($id)     : null;
        $freelancer = $user->getRole() === 'FREELANCER' ? $freelancerRepo->findByUserId($id) : null;

        return $this->render('backOffice/user/show.html.twig', [
            'user'       => $user,
            'client'     => $client,
            'freelancer' => $freelancer,
        ]);
    }
}