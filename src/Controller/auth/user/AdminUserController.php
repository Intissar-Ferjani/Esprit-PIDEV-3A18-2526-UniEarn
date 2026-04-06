<?php

namespace App\Controller\auth\user;

use App\Entity\users\user\User;
use App\Repository\users\user\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    // ── LIST ───────────────────────────────────────────────────────────

    #[Route('/', name: 'admin_manage_users', methods: ['GET'])]
    public function index(Request $request, UserRepository $repo): Response
    {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        return $this->render('backOffice/user/list-users.html.twig', [
            'users' => $repo->findAllUsers(),
        ]);
    }

    // ── ACTIVATE / DEACTIVATE ──────────────────────────────────────────

    #[Route('/{id}/toggle', name: 'admin_user_toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em): Response
    {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        $user = $repo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $user->setActivated(!$user->isActivated());
        $em->flush();

        $status = $user->isActivated() ? 'reactivated' : 'deactivated';
        $this->addFlash('success', "User {$user->getName()} has been {$status}.");

        return $this->redirectToRoute('admin_manage_users');
    }

    // ── DELETE ─────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(int $id, UserRepository $repo, EntityManagerInterface $em, Request $request): Response
    {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        $user = $repo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        if ($this->isCsrfTokenValid('delete_user_' . $id, $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('admin_manage_users');
    }

    // ── VIEW DETAILS ───────────────────────────────────────────────────

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(int $id, Request $request, UserRepository $repo): Response
    {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        $user = $repo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        return $this->render('backOffice/user/show.html.twig', ['user' => $user]);
    }
}