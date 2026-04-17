<?php

namespace App\Controller\auth\user;

use App\Repository\users\user\UserRepository;
use App\Service\users\user\EmailService;
use App\Service\users\user\LoginAttemptService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;

class IntruderController extends AbstractController
{
    #[Route('/user/intruder/capture', name: 'user_intruder_capture', methods: ['POST'])]
    public function capture(Request $request, UserRepository $userRepository, EmailService $emailService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $imageData = $data['image'] ?? null;

        if (!$email || !$imageData) {
            return new JsonResponse(['error' => 'Missing data'], 400);
        }

        $user = $userRepository->findByEmail($email);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Save Photo
        $photoPath = null;
        try {
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageBinary = base64_decode($imageData);

            $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/intrusion';
            $fs = new Filesystem();
            if (!$fs->exists($dir)) {
                $fs->mkdir($dir);
            }

            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9@._-]/', '_', $email) . '.png';
            $photoPath = $dir . '/' . $fileName;
            file_put_contents($photoPath, $imageBinary);
            
            // Public path for the email CID
            $photoPath = 'uploads/intrusion/' . $fileName;
        } catch (\Exception $e) {
            // Log error but continue with email without photo
        }

        // Generate Tokens for Callbacks (simple simulation)
        // In a production app, these should be signed JWTs or stored in DB
        $confirmToken = base64_encode($email . ':confirm');
        $lockToken = base64_encode($email . ':lock');

        // Send Email
        try {
            $emailService->sendIntruderAlert(
                $user,
                $request->getClientIp(),
                $photoPath ? $this->getParameter('kernel.project_dir') . '/public/' . $photoPath : null,
                $confirmToken,
                $lockToken
            );
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Email failed: ' . $e->getMessage()], 500);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/user/intruder/confirm', name: 'user_intruder_confirm')]
    public function confirm(Request $request, LoginAttemptService $attemptService): Response
    {
        $token = $request->query->get('token');
        $data = base64_decode($token);
        if ($data && str_contains($data, ':confirm')) {
            $email = explode(':', $data)[0];
            $attemptService->reset($email);
            $this->addFlash('success', 'Security alert confirmed. Your account is now unlocked.');
            return $this->redirectToRoute('user_forgot_password');
        }
        return $this->redirectToRoute('user_login');
    }

    #[Route('/user/intruder/lockme', name: 'user_intruder_lockme')]
    public function lockme(Request $request, LoginAttemptService $attemptService): Response
    {
        $token = $request->query->get('token');
        $data = base64_decode($token);
        if ($data && str_contains($data, ':lock')) {
            $email = explode(':', $data)[0];
            $attemptService->lockManually($email);
            $this->addFlash('warning', 'Account has been manually locked for 15 minutes for your security.');
        }
        return $this->redirectToRoute('user_login');
    }
}
