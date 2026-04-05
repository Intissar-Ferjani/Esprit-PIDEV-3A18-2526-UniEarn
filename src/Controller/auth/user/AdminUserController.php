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

    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('backOffice/user/index.html.twig', [
            'users' => $repo->findAllUsers(),
        ]);
    }

    // ── ACTIVATE / DEACTIVATE ──────────────────────────────────────────

    #[Route('/{id}/toggle', name: 'admin_user_toggle', methods: ['POST'])]
    public function toggle(int $id, UserRepository $repo, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $repo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $user->setActivated(!$user->isActivated());
        $em->flush();

        $status = $user->isActivated() ? 'reactivated' : 'deactivated';
        $this->addFlash('success', "User {$user->getName()} has been {$status}.");

        return $this->redirectToRoute('admin_user_index');
    }

    // ── DELETE ─────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(int $id, UserRepository $repo, EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $repo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        if ($this->isCsrfTokenValid('delete_user_' . $id, $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    // ── VIEW DETAILS ───────────────────────────────────────────────────

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(int $id, UserRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $repo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        return $this->render('backOffice/user/show.html.twig', ['user' => $user]);
    }
}