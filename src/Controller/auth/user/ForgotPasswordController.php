<?php

namespace App\Controller\auth\user;

use App\Repository\users\user\UserRepository;
use App\Service\users\user\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'user_forgot_password')]
    public function forgotPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, EmailService $emailService): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findByEmail($email);

            if (!$user) {
                $this->addFlash('error', 'No account found with this email address.');
                return $this->redirectToRoute('user_forgot_password');
            }

            // Generate 6-digit code
            $resetToken = sprintf('%06d', mt_rand(0, 999999));
            $user->setResetToken($resetToken);
            $user->setResetTokenExpiresAt(new \DateTimeImmutable('+15 minutes'));

            $entityManager->flush();

            // Send Email
            try {
                $emailService->sendPasswordResetEmail($user->getEmail(), $resetToken);
                $request->getSession()->set('reset_email', $user->getEmail());
                $this->addFlash('success', 'A reset code has been sent to your email.');
                return $this->redirectToRoute('user_reset_password');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to send email. Please try again later.');
            }
        }

        return $this->render('frontOffice/user/auth/forgot-password.html.twig');
    }

    #[Route('/reset-password', name: 'user_reset_password')]
    public function resetPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $sessionEmail = $request->getSession()->get('reset_email');
        if (!$sessionEmail) {
            return $this->redirectToRoute('user_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');
            $user = $userRepository->findByEmail($sessionEmail);

            if (!$user || $user->getResetToken() !== $code) {
                $this->addFlash('error', 'Invalid reset code.');
                return $this->redirectToRoute('user_reset_password');
            }

            if ($user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
                $this->addFlash('error', 'Reset code has expired.');
                return $this->redirectToRoute('user_forgot_password');
            }

            // Code verified! Save to session and move to next step
            $request->getSession()->set('reset_code_verified', true);
            return $this->redirectToRoute('user_choose_new_password');
        }

        return $this->render('frontOffice/user/auth/reset-password.html.twig');
    }

    #[Route('/choose-new-password', name: 'user_choose_new_password')]
    public function chooseNewPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $sessionEmail = $request->getSession()->get('reset_email');
        $codeVerified = $request->getSession()->get('reset_code_verified');

        if (!$sessionEmail || !$codeVerified) {
            return $this->redirectToRoute('user_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->redirectToRoute('user_choose_new_password');
            }

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Password must be at least 8 characters long.');
                return $this->redirectToRoute('user_choose_new_password');
            }

            $user = $userRepository->findByEmail($sessionEmail);
            if (!$user) {
                return $this->redirectToRoute('user_forgot_password');
            }

            // Update Password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $user->setPassword($hashedPassword);
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $entityManager->flush();

            // Clear session flags
            $request->getSession()->remove('reset_email');
            $request->getSession()->remove('reset_code_verified');

            $this->addFlash('success', 'Your password has been reset successfully. You can now log in.');
            return $this->redirectToRoute('user_login');
        }

        return $this->render('frontOffice/user/auth/choose-new-password.html.twig');
    }

    #[Route('/resend-code', name: 'user_resend_code')]
    public function resendCode(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, EmailService $emailService): Response
    {
        $sessionEmail = $request->getSession()->get('reset_email');
        if (!$sessionEmail) {
            return $this->redirectToRoute('user_forgot_password');
        }

        $user = $userRepository->findByEmail($sessionEmail);
        if (!$user) {
            return $this->redirectToRoute('user_forgot_password');
        }

        // Generate NEW 6-digit code
        $resetToken = sprintf('%06d', mt_rand(0, 999999));
        $user->setResetToken($resetToken);
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+15 minutes'));

        $entityManager->flush();

        try {
            $emailService->sendPasswordResetEmail($user->getEmail(), $resetToken);
            $this->addFlash('success', 'A new reset code has been sent to your email.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send email. Please try again later.');
        }

        return $this->redirectToRoute('user_reset_password');
    }
}
