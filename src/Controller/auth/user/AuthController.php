<?php

namespace App\Controller\auth\user;

use App\Entity\users\admin\Admin;
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

            if ($userRepo->findByEmail($user->getEmail())) {
                $this->addFlash('error', 'This email address is already registered.');
                return $this->render('frontOffice/user/auth/signup.html.twig', ['form' => $form]);
            }

            $user->setPassword($form->get('plainPassword')->getData());
            $user->setEmail(strtolower(trim($user->getEmail())));
            $user->setActivated(true);

            $pictureFile = $form->get('profilePictureFile')->getData();
            if ($pictureFile) {
                $safeFilename = $slugger->slug(pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . $pictureFile->guessExtension();
                try {
                    $pictureFile->move($this->getParameter('profile_pictures_directory'), $newFilename);
                    $user->setProfilePicturePath('uploads/profiles/' . $newFilename);
                } catch (FileException) {
                    $this->addFlash('error', 'Could not upload profile picture.');
                }
            }

            $em->persist($user);
            $em->flush();

            // ADMIN — one step only, create admin row + log in immediately
            if ($user->getRole() === 'ADMIN') {
                $admin = new Admin();
                $admin->setUser($user);
                $em->persist($admin);
                $em->flush();

                $request->getSession()->set('user_id',   $user->getIdUser());
                $request->getSession()->set('user_name', $user->getName());
                $request->getSession()->set('user_role', 'ADMIN');

                $this->addFlash('success', 'Admin account created. Welcome!');
                return $this->redirectToRoute('admin_dashboard');
            }

            // CLIENT / FREELANCER — go to step 2
            $request->getSession()->set('pending_user_id', $user->getIdUser());

            if ($user->getRole() === 'CLIENT')     return $this->redirectToRoute('client_setup');
            if ($user->getRole() === 'FREELANCER') return $this->redirectToRoute('freelancer_setup');

            return $this->redirectToRoute('user_login');
        }
        return $this->render('frontOffice/user/auth/signup.html.twig', ['form' => $form]);
    }

    #[Route('/login', name: 'user_login', methods: ['GET', 'POST'])]
    public function login(Request $request, UserRepository $userRepo): Response
    {
        if ($request->isMethod('POST')) {
            $email    = strtolower(trim($request->request->get('email')));
            $password = $request->request->get('password');
            $user     = $userRepo->findByEmail($email);

            if (!$user) {
                $this->addFlash('error', 'No account found with that email.');
                return $this->render('frontOffice/user/auth/login.html.twig', ['last_email' => $email]);
            }
            if (!$user->isActivated()) {
                $this->addFlash('error', 'Your account has been deactivated.');
                return $this->render('frontOffice/user/auth/login.html.twig', ['last_email' => $email]);
            }
            $dbPassword = $user->getPassword();
            $isMatch = false;

            // 1. Try hashed comparison (supporting $2a$ or $2y$ prefixes)
            if (str_starts_with($dbPassword, '$2')) {
                if (password_verify($password, $dbPassword)) {
                    $isMatch = true;
                }
            } 
            
            // 2. Fallback to plain text comparison (for legacy users)
            if (!$isMatch && $dbPassword === $password) {
                $isMatch = true;
            }

            if (!$isMatch) {
                $this->addFlash('error', 'Incorrect password.');
                return $this->render('frontOffice/user/auth/login.html.twig', ['last_email' => $email]);
            }

            $request->getSession()->set('user_id',   $user->getIdUser());
            $request->getSession()->set('user_name', $user->getName());
            $request->getSession()->set('user_role', $user->getRole());

            $this->addFlash('success', 'Welcome back, ' . $user->getName() . '!');

            if ($user->getRole() === 'ADMIN') {
                return $this->redirectToRoute('admin_user_index');
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

    #[Route('/logout', name: 'user_logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();
        return $this->redirectToRoute('user_login');
    }

    #[Route('/dashboard', name: 'user_dashboard')]
    public function dashboard(Request $request, UserRepository $userRepo): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $user = $userRepo->find($userId);
        if (!$user) return $this->redirectToRoute('user_login');

        if ($user->getRole() === 'ADMIN')      return $this->redirectToRoute('admin_dashboard');
        if ($user->getRole() === 'CLIENT')     return $this->redirectToRoute('client_dashboard');
        if ($user->getRole() === 'FREELANCER') return $this->redirectToRoute('freelancer_dashboard');

        return $this->render('frontOffice/user/auth/dashboard.html.twig', ['user' => $user]);
    }
}