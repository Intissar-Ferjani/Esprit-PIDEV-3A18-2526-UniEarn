<?php

namespace App\Controller\auth\user;

use App\Entity\users\user\User;
use App\Form\users\user\RegistrationFormType;
use App\Repository\users\user\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/user')]
class AuthController extends AbstractController
{
    // ── SIGNUP (Step 1 — common for all roles) ─────────────────────────

    #[Route('/signup', name: 'user_signup', methods: ['GET', 'POST'])]
    public function signup(
        Request                $request,
        EntityManagerInterface $em,
        UserRepository         $userRepo,
        SluggerInterface       $slugger
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Check duplicate email
            if ($userRepo->findByEmail($user->getEmail())) {
                $this->addFlash('error', 'This email address is already registered.');
                return $this->render('frontOffice/user/auth/signup.html.twig', ['form' => $form]);
            }

            // Store plain password (no hashing for now)
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($plainPassword);
            $user->setEmail(strtolower(trim($user->getEmail())));
            $user->setActivated(true);

            // Handle profile picture upload
            $pictureFile = $form->get('profilePictureFile')->getData();
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = $slugger->slug($originalFilename);
                $newFilename      = $safeFilename . '-' . uniqid() . '.' . $pictureFile->guessExtension();

                try {
                    $pictureFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                    $user->setProfilePicturePath('uploads/profiles/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Could not upload profile picture.');
                }
            }

            $em->persist($user);
            $em->flush();

            // Store user ID in session as "pending" — step 2 will finalize login
            $request->getSession()->set('pending_user_id', $user->getIdUser());

            // Redirect to the correct step 2 based on role
            if ($user->getRole() === 'CLIENT') {
                return $this->redirectToRoute('client_setup');
            }

            if ($user->getRole() === 'FREELANCER') {
                return $this->redirectToRoute('freelancer_setup');
            }

            // Fallback (should not happen for normal users)
            return $this->redirectToRoute('user_login');
        }

        return $this->render('frontOffice/user/auth/signup.html.twig', ['form' => $form]);
    }

    // ── LOGIN ──────────────────────────────────────────────────────────

    #[Route('/login', name: 'user_login', methods: ['GET', 'POST'])]
    public function login(Request $request, UserRepository $userRepo): Response
    {
        if ($request->isMethod('POST')) {
            $email    = strtolower(trim($request->request->get('email')));
            $password = $request->request->get('password');

            $user = $userRepo->findByEmail($email);

            if (!$user) {
                $this->addFlash('error', 'No account found with that email.');
                return $this->render('frontOffice/user/auth/login.html.twig', ['last_email' => $email]);
            }

            if (!$user->isActivated()) {
                $this->addFlash('error', 'Your account has been deactivated. Please contact support.');
                return $this->render('frontOffice/user/auth/login.html.twig', ['last_email' => $email]);
            }

            if ($user->getPassword() !== $password) {
                $this->addFlash('error', 'Incorrect password.');
                return $this->render('frontOffice/user/auth/login.html.twig', ['last_email' => $email]);
            }

            // Store user in session
            $session = $request->getSession();
            $session->set('user_id',   $user->getIdUser());
            $session->set('user_name', $user->getName());
            $session->set('user_role', $user->getRole());

            $this->addFlash('success', 'Welcome back, ' . $user->getName() . '!');

            if ($user->getRole() === 'ADMIN') {
                return $this->redirectToRoute('admin_manage_users');
            }
            if ($user->getRole() === 'CLIENT') {
                return $this->redirectToRoute('client_dashboard');
            }
            if ($user->getRole() === 'FREELANCER') {
                return $this->redirectToRoute('freelancer_dashboard');
            }

            return $this->redirectToRoute('user_dashboard');
        }

        return $this->render('frontOffice/user/auth/login.html.twig', ['last_email' => '']);
    }

    // ── LOGOUT ─────────────────────────────────────────────────────────

    #[Route('/logout', name: 'user_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();
        return $this->redirectToRoute('user_login');
    }

    // ── DASHBOARD ──────────────────────────────────────────────────────

    #[Route('/dashboard', name: 'user_dashboard')]
    public function dashboard(Request $request, UserRepository $userRepo): Response
    {
        $userId = $request->getSession()->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->redirectToRoute('user_login');
        }

        return $this->render('frontOffice/user/profile/dashboard.html.twig', ['user' => $user]);
    }
}