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
use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\users\freelancer\PortfolioRepository;
use App\Repository\users\freelancer\PortfolioItemRepository;
use App\Form\users\client\ClientEditFormType;

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
            'viewerRole' => 'CLIENT',
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

        $form = $this->createForm(ClientEditFormType::class, $client);
        $form->get('name')->setData($user->getName());
        $form->get('email')->setData($user->getEmail());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name  = $form->get('name')->getData();
            $email = strtolower(trim($form->get('email')->getData()));

            // Email uniqueness check
            $existing = $userRepo->findByEmail($email);
            if ($existing && (int)$existing->getIdUser() !== (int)$user->getIdUser()) {
                $this->addFlash('error', 'This email is already used by another account.');
                return $this->render('frontOffice/client/profile/edit-profile.html.twig', [
                    'form'   => $form->createView(),
                    'client' => $client,
                    'user'   => $user,
                ]);
            }

            // Password change
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword     = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

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
                    return $this->render('frontOffice/client/profile/edit-profile.html.twig', [
                        'form'   => $form->createView(),
                        'client' => $client,
                        'user'   => $user,
                    ]);
                }
                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'New passwords do not match.');
                    return $this->render('frontOffice/client/profile/edit-profile.html.twig', [
                        'form'   => $form->createView(),
                        'client' => $client,
                        'user'   => $user,
                    ]);
                }
                $user->setPassword($newPassword);
            }

            $user->setName($name);
            $user->setEmail($email);
            $em->flush();
            $request->getSession()->set('user_name', $name);

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('client_dashboard');
        }

        return $this->render('frontOffice/client/profile/edit-profile.html.twig', [
            'form'   => $form->createView(),
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


    #[Route('/freelancers/{id}', name: 'client_view_freelancer', methods: ['GET'])]
    public function viewFreelancers(
        int                     $id,
        Request                 $request,
        FreelancerRepository    $freelancerRepo,
        PortfolioRepository     $portfolioRepo,
        PortfolioItemRepository $itemRepo,
        ClientRepository        $clientRepo
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');
 
        $client     = $clientRepo->findByUserId($userId);
        $freelancer = $freelancerRepo->find($id);
 
        if (!$freelancer) throw $this->createNotFoundException('Freelancer not found.');
 
        $portfolio = $portfolioRepo->findByFreelancerId($freelancer->getIdFreelancer());
        $items     = $portfolio ? $itemRepo->findByPortfolioId($portfolio->getIdPortfolio()) : [];
 
        // Reuse the same freelancer dashboard template, but pass viewerRole = 'CLIENT'
        // so the template hides edit/photo controls
        return $this->render('frontOffice/freelancer/profile/dashboard.html.twig', [
            'freelancer'  => $freelancer,
            'user'        => $freelancer->getUser(),
            'viewerRole'  => 'CLIENT',
            'backUrl'     => $this->generateUrl('client_browse_freelancers'),
            'portfolio'   => $portfolio,
            'items'       => $items,
            'sidebarInclude' => 'frontOffice/user/profile/_sidebar.html.twig',
            'sidebarActive'  => 'freelancers',
        ]);
    }
}