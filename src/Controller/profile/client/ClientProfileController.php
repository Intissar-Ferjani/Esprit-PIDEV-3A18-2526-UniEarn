<?php

namespace App\Controller\profile\client;

use App\Repository\users\client\ClientRepository;
use App\Repository\users\user\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/client')]
class ClientProfileController extends AbstractController
{
    // ── DASHBOARD ──────────────────────────────────────────────────────

    #[Route('/dashboard', name: 'client_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, ClientRepository $clientRepo): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $client = $clientRepo->findByUserId($userId);
        if (!$client) return $this->redirectToRoute('user_login');

        return $this->render('frontOffice/client/profile/dashboard.html.twig', [
            'client' => $client,
            'user'   => $client->getUser(),
        ]);
    }

    // ── EDIT PROFILE ───────────────────────────────────────────────────

    #[Route('/profile/edit', name: 'client_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(
        Request                $request,
        ClientRepository       $clientRepo,
        UserRepository         $userRepo,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $client = $clientRepo->findByUserId($userId);
        if (!$client) return $this->redirectToRoute('user_login');

        $user = $client->getUser();

        if ($request->isMethod('POST')) {
            $name     = trim($request->request->get('name', ''));
            $email    = strtolower(trim($request->request->get('email', '')));
            $company  = trim($request->request->get('company', ''));
            $industry = trim($request->request->get('industry', ''));

            // Validate
            if (!$name || !$email) {
                $this->addFlash('error', 'Name and email are required.');
                return $this->redirectToRoute('client_profile_edit');
            }

            // Check email uniqueness (excluding current user)
            $existing = $userRepo->findByEmail($email);
            if ($existing && $existing->getIdUser() !== $user->getIdUser()) {
                $this->addFlash('error', 'This email is already used by another account.');
                return $this->redirectToRoute('client_profile_edit');
            }

            // Password change
            $currentPassword = $request->request->get('current_password', '');
            $newPassword     = $request->request->get('new_password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            if ($newPassword !== '') {
                $dbPassword = $user->getPassword();
                $isCurrentMatch = false;

                if (str_starts_with($dbPassword, '$2') && password_verify($currentPassword, $dbPassword)) {
                    $isCurrentMatch = true;
                } elseif ($dbPassword === $currentPassword) {
                    $isCurrentMatch = true;
                }

                if (!$isCurrentMatch) {
                    $this->addFlash('error', 'Current password is incorrect.');
                    return $this->redirectToRoute('client_profile_edit');
                }
                if (strlen($newPassword) < 8) {
                    $this->addFlash('error', 'New password must be at least 8 characters.');
                    return $this->redirectToRoute('client_profile_edit');
                }
                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'New passwords do not match.');
                    return $this->redirectToRoute('client_profile_edit');
                }
                $user->setPassword($newPassword);
            }

            $user->setName($name);
            $user->setEmail($email);
            $client->setCompany($company);
            $client->setIndustry($industry);

            $em->flush();

            // Update session name
            $request->getSession()->set('user_name', $name);

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('client_dashboard');
        }

        return $this->render('frontOffice/client/profile/edit-profile.html.twig', [
            'client' => $client,
            'user'   => $user,
        ]);
    }

    // ── CHANGE PHOTO ───────────────────────────────────────────────────

    #[Route('/profile/photo', name: 'client_change_photo', methods: ['POST'])]
    public function changePhoto(
        Request                $request,
        ClientRepository       $clientRepo,
        EntityManagerInterface $em,
        SluggerInterface       $slugger
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $client = $clientRepo->findByUserId($userId);
        if (!$client) return $this->redirectToRoute('user_login');

        $photoFile = $request->files->get('profile_photo');
        if ($photoFile) {
            $newFilename = 'client_' . $userId . '_' . uniqid() . '.' . $photoFile->guessExtension();
            try {
                $photoFile->move(
                    $this->getParameter('profile_pictures_directory'),
                    $newFilename
                );
                $client->getUser()->setProfilePicturePath('uploads/profiles/' . $newFilename);
                $em->flush();
                $this->addFlash('success', 'Profile photo updated!');
            } catch (FileException) {
                $this->addFlash('error', 'Could not upload photo.');
            }
        }

        return $this->redirectToRoute('client_dashboard');
    }

    // ── DEACTIVATE ACCOUNT ─────────────────────────────────────────────

    #[Route('/profile/deactivate', name: 'client_deactivate', methods: ['POST'])]
    public function deactivate(
        Request                $request,
        ClientRepository       $clientRepo,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $confirm = $request->request->get('confirm_text', '');
        if (strtoupper($confirm) !== 'DEACTIVATE') {
            $this->addFlash('error', 'You must type DEACTIVATE to confirm.');
            return $this->redirectToRoute('client_profile_edit');
        }

        $client = $clientRepo->findByUserId($userId);
        if ($client) {
            $client->getUser()->setActivated(false);
            $em->flush();
        }

        $request->getSession()->invalidate();
        $this->addFlash('success', 'Your account has been deactivated.');
        return $this->redirectToRoute('user_login');
    }
}