<?php

namespace App\Controller\auth\user;

use App\Entity\users\user\User;
use App\Repository\users\user\UserRepository;
use App\Service\users\user\AvatarService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process
     */
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'openid', 'email', 'profile'
            ]);
    }

    /**
     * After going to Google, you're redirected back here because this is the "redirect_route"
     * configured in config/packages/knpu_oauth2_client.yaml
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, UserRepository $userRepo, EntityManagerInterface $em, AvatarService $avatarService): Response
    {
        /** @var \KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient $client */
        $client = $clientRegistry->getClient('google');

        try {
            /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
            $googleUser = $client->fetchUser();

            $email = $googleUser->getEmail();
            $user = $userRepo->findByEmail($email);

            if ($user) {
                // User exists, log them in
                if (!$user->isActivated()) {
                    $this->addFlash('error', 'Your account has been deactivated. Please contact support.');
                    return $this->redirectToRoute('user_login');
                }

                $this->loginUserManual($request, $user);
                $this->addFlash('success', 'Welcome back, ' . $user->getName() . '!');
                
                // Redirect based on role to be more explicit
                if ($user->getRole() === 'ADMIN')      return $this->redirectToRoute('admin_manage_users');
                if ($user->getRole() === 'CLIENT')     return $this->redirectToRoute('client_dashboard');
                if ($user->getRole() === 'FREELANCER') return $this->redirectToRoute('freelancer_dashboard');

                return $this->redirectToRoute('user_dashboard');
            } else {
                // New user - create partial user and redirect to role selection
                $user = new User();
                $user->setEmail($email);
                $user->setName($googleUser->getName());
                
                // Placeholder password matching Java logic
                $user->setPassword('GOOGLE_OAUTH_' . $googleUser->getId());
                $user->setActivated(true);

                // Download Avatar
                $googleImage = $googleUser->getAvatar();
                if ($googleImage) {
                    $avatarPath = $avatarService->downloadGoogleAvatar($googleImage, $email);
                    if ($avatarPath) {
                        $user->setProfilePicturePath($avatarPath);
                    }
                }

                $em->persist($user);
                $em->flush();

                // Store in session as "pending" for role selection
                $request->getSession()->set('pending_google_user_id', $user->getIdUser());
                
                return $this->redirectToRoute('user_google_role_selection');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Google login failed: ' . $e->getMessage());
            return $this->redirectToRoute('user_login');
        }
    }

    #[Route('/connect/google/role-selection', name: 'user_google_role_selection')]
    public function roleSelection(Request $request, UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('pending_google_user_id');
        if (!$userId) return $this->redirectToRoute('user_signup');

        $user = $userRepo->find($userId);
        if (!$user) return $this->redirectToRoute('user_signup');

        if ($request->isMethod('POST')) {
            $role = $request->request->get('role');
            if (in_array($role, ['CLIENT', 'FREELANCER'])) {
                $user->setRole($role);
                $em->flush();

                $request->getSession()->remove('pending_google_user_id');
                
                // CRITICAL: Set pending_user_id so setup steps (Step 2) can find this user
                $request->getSession()->set('pending_user_id', $user->getIdUser());

                // If freelancer, go to setup. If client, go to setup.
                if ($role === 'CLIENT')     return $this->redirectToRoute('client_setup');
                if ($role === 'FREELANCER') return $this->redirectToRoute('freelancer_setup');
            }
        }

        return $this->render('frontOffice/user/auth/google-role-selection.html.twig', [
            'user_name' => $user->getName()
        ]);
    }

    private function loginUserManual(Request $request, User $user): void
    {
        $request->getSession()->set('user_id',   $user->getIdUser());
        $request->getSession()->set('user_name', $user->getName());
        $request->getSession()->set('user_role', $user->getRole());
    }
}
