<?php

namespace App\Controller\auth\freelancer;

use App\Entity\users\freelancer\Freelancer;
use App\Entity\users\freelancer\Portfolio;
use App\Entity\users\freelancer\PortfolioItem;
use App\Form\users\freelancer\FreelancerProfileFormType;
use App\Form\users\freelancer\PortfolioFormType;
use App\Form\users\freelancer\StudentCardFormType;
use App\Repository\users\freelancer\FreelancerRepository;
use App\Service\StudentCardOCRService;
use App\Service\CvAIService;
use App\Repository\users\user\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/user/freelancer')]
class FreelancerSignupController extends AbstractController
{
    // ── STEP 2 — Profile ───────────────────────────────────────────────

    #[Route('/setup', name: 'freelancer_setup', methods: ['GET', 'POST'])]
    public function setup(
        Request                $request,
        EntityManagerInterface $em,
        UserRepository         $userRepo,
        FreelancerRepository   $freelancerRepo,
        SluggerInterface       $slugger
    ): Response {
        $userId = $request->getSession()->get('pending_user_id');
        if (!$userId) return $this->redirectToRoute('user_signup');

        $user = $userRepo->find($userId);
        if (!$user || $user->getRole() !== 'FREELANCER') return $this->redirectToRoute('user_signup');

        // Look for existing freelancer data to restore
        $freelancer = $freelancerRepo->findOneBy(['user' => $user]) ?: new Freelancer();
        
        $form       = $this->createForm(FreelancerProfileFormType::class, $freelancer);        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $cvFile = $form->get('cvFile')->getData();

            if ($cvFile) {
                $newFilename = uniqid().'.'.$cvFile->guessExtension();

                try {
                    $cvFile->move(
                        $this->getParameter('cv_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'File upload failed.');
                }

                $freelancer->setCvPath($newFilename);
            }

            $freelancer->setUser($user); 

            $em->persist($freelancer);
            $em->flush();

            // store freelancer id for next step
            $request->getSession()->set('pending_freelancer_id', $freelancer->getIdFreelancer());

            return $this->redirectToRoute('freelancer_verify');
        }
        return $this->render('frontOffice/freelancer/auth/freelancer-information.html.twig', ['form' => $form, 'user' => $user]);
    }

    // ── STEP 2b — AI Bio Generation (AJAX) ────────────────────────────

    #[Route('/generate-bio', name: 'freelancer_generate_bio', methods: ['POST'])]
    public function generateBio(
        Request        $request,
        UserRepository $userRepo,
        CvAIService    $cvAIService
    ): Response {
        try {
            $userId = $request->getSession()->get('pending_user_id');
            if (!$userId) {
                return $this->json(['success' => false, 'message' => 'Session expired.'], 403);
            }

            $user = $userRepo->find($userId);
            if (!$user) {
                return $this->json(['success' => false, 'message' => 'User not found.'], 404);
            }

            $file = $request->files->get('cvFile');

            if (!$file || !$file->isValid()) {
                $error = $file ? $file->getErrorMessage() : 'No file uploaded.';
                return $this->json(['success' => false, 'message' => 'Upload failed: ' . $error], 400);
            }

            if ($file->getSize() > 10 * 1024 * 1024) {
                return $this->json(['success' => false, 'message' => 'File too large (max 10MB).'], 400);
            }

            // Save to cv directory
            $newFilename = 'cv_' . $userId . '_' . uniqid() . '.' . $file->guessExtension();
            $file->move($this->getParameter('cv_directory'), $newFilename);
            $fullPath = $this->getParameter('cv_directory') . '/' . $newFilename;

            // Store the CV path in session so setup can use it
            $request->getSession()->set('pending_cv_path', $newFilename);

            // Generate bio
            $result = $cvAIService->generateBioFromCv($fullPath, $user->getName());

            return $this->json($result);

        } catch (\Throwable $e) {
            error_log("Bio Generation Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->json([
                'success' => false,
                'message' => 'Bio generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ── STEP 3 — Student Card Verification ────────────────────────────

    #[Route('/verify', name: 'freelancer_verify', methods: ['GET', 'POST'])]
    public function verify(
        Request                $request,
        EntityManagerInterface $em,
        UserRepository         $userRepo,
        FreelancerRepository   $freelancerRepo
    ): Response {
        $userId       = $request->getSession()->get('pending_user_id');
        $freelancerId = $request->getSession()->get('pending_freelancer_id');

        if (!$userId || !$freelancerId) return $this->redirectToRoute('user_signup');

        $user       = $userRepo->find($userId);
        $freelancer = $freelancerRepo->find($freelancerId);

        if (!$user || !$freelancer) return $this->redirectToRoute('user_signup');

        // Restore card status if already uploaded
        $existingCard = $freelancer->getStudentCardPath();
        if ($existingCard && !$request->getSession()->has('card_path')) {
            $request->getSession()->set('card_path', $existingCard);
            $request->getSession()->set('card_verified', $freelancer->getVerificationStatus() === 'verified');
        }

        $form = $this->createForm(StudentCardFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Check if card was verified via the separate process step
            $session = $request->getSession();
            $cardVerified = $session->get('card_verified', false);
            $cardPath     = $session->get('card_path');

            if (!$cardPath) {
                $this->addFlash('error', 'Please upload and verify your student card first.');
                return $this->render('frontOffice/freelancer/auth/student-id-verification.html.twig', ['form' => $form, 'user' => $user]);
            }

            $freelancer->setStudentCardPath($cardPath);
            $freelancer->setVerificationStatus($cardVerified ? 'verified' : 'unverified');
            
            $em->persist($freelancer);
            $em->flush();

            // Clear temporary session data
            $session->remove('card_verified');
            $session->remove('card_path');

            return $this->redirectToRoute('freelancer_portfolio');
        }

        return $this->render('frontOffice/freelancer/auth/student-id-verification.html.twig', [
            'form' => $form, 
            'user' => $user
        ]);
    }

    #[Route('/verify-card-process', name: 'freelancer_verify_card_process', methods: ['POST'])]
    public function processCard(
        Request                $request,
        UserRepository         $userRepo,
        FreelancerRepository   $freelancerRepo,
        StudentCardOCRService  $ocrService
    ): Response {
        try {
            $userId       = $request->getSession()->get('pending_user_id');
            $freelancerId = $request->getSession()->get('pending_freelancer_id');

            if (!$userId || !$freelancerId) {
                return $this->json(['success' => false, 'message' => 'Session expired.'], 403);
            }

            $user       = $userRepo->find($userId);
            $freelancer = $freelancerRepo->find($freelancerId);

            $file = $request->files->get('studentCardFile');
            
            if (!$file || !$file->isValid()) {
                $error = $file ? $file->getErrorMessage() : 'No file uploaded.';
                return $this->json(['success' => false, 'message' => 'Upload failed: ' . $error], 400);
            }

            // Validate file size and type manually for the AJAX call
            if ($file->getSize() > 10 * 1024 * 1024) { // Increased limit to 10MB just in case
                return $this->json(['success' => false, 'message' => 'File too large (max 10MB).'], 400);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!in_array($file->getMimeType(), $allowedTypes)) {
                return $this->json(['success' => false, 'message' => 'Invalid file type. JPG, PNG or PDF required.'], 400);
            }

            $newFilename = 'card_' . $userId . '_' . uniqid() . '.' . $file->guessExtension();

            $file->move($this->getParameter('student_cards_directory'), $newFilename);
            $fullPath = $this->getParameter('student_cards_directory') . '/' . $newFilename;
            $relativePath = 'uploads/student_cards/' . $newFilename;

            // Run OCR
            $result = $ocrService->verifyStudentCard($fullPath, $user->getName());

            // Store in session
            $request->getSession()->set('card_verified', $result['verified']);
            $request->getSession()->set('card_path', $relativePath);

            return $this->json([
                'success'  => $result['verified'],
                'message'  => $result['message'],
                'filename' => $newFilename
            ]);

        } catch (\Throwable $e) {
            error_log("AJAX Verification Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->json([
                'success' => false, 
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ── STEP 4 — Portfolio ─────────────────────────────────────────────

    #[Route('/portfolio', name: 'freelancer_portfolio', methods: ['GET', 'POST'])]
    public function portfolio(
        Request                $request,
        EntityManagerInterface $em,
        UserRepository         $userRepo,
        FreelancerRepository   $freelancerRepo
    ): Response {
        $userId       = $request->getSession()->get('pending_user_id');
        $freelancerId = $request->getSession()->get('pending_freelancer_id');

        if (!$userId || !$freelancerId) return $this->redirectToRoute('user_signup');

        $user       = $userRepo->find($userId);
        $freelancer = $freelancerRepo->find($freelancerId);

        if (!$user || !$freelancer) return $this->redirectToRoute('user_signup');

        $form = $this->createForm(PortfolioFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $portfolioTitle = trim($form->get('portfolioTitle')->getData() ?? '');
            $portfolioDesc  = trim($form->get('portfolioDescription')->getData() ?? '');
            $projectTitle   = trim($form->get('projectTitle')->getData() ?? '');

            // If portfolio data was provided, save it
            if ($portfolioTitle || $portfolioDesc) {

                if ($portfolioTitle && strlen($portfolioTitle) < 5) {
                    $this->addFlash('error', 'Portfolio title must be at least 5 characters.');
                    return $this->render('frontOffice/freelancer/auth/portfolio-information.html.twig', ['form' => $form, 'user' => $user]);
                }

                $portfolio = new Portfolio();
                $portfolio->setTitle($portfolioTitle ?: $user->getName() . "'s Portfolio");
                $portfolio->setDescription($portfolioDesc ?: 'My professional portfolio');
                $portfolio->setFreelancer($freelancer);

                $em->persist($portfolio);
                $em->flush(); // flush to get portfolio ID

                // Save portfolio item if project title was filled
                if ($projectTitle) {
                    $item = new PortfolioItem();
                    $item->setTitle($projectTitle);
                    $item->setDescription(trim($form->get('projectDescription')->getData() ?? ''));
                    $item->setTechnologiesFromString($form->get('technologies')->getData());
                    $item->setProjectUrl($form->get('projectUrl')->getData());
                    $item->setGithubUrl($form->get('githubUrl')->getData());
                    $item->setImageUrl('[]');
                    $item->setPortfolio($portfolio);

                    $em->persist($item);
                    $em->flush();
                }
            }

            // All steps done — log the user in
            $this->finalizeLogin($request, $user);

            $this->addFlash('success', 'Welcome to UniEarn, ' . $user->getName() . '! Your freelancer profile is ready.');
            return $this->redirectToRoute('freelancer_dashboard');
        }

        return $this->render('frontOffice/freelancer/auth/portfolio-information.html.twig', ['form' => $form, 'user' => $user]);
    }

    // ── Skip portfolio ─────────────────────────────────────────────────

    #[Route('/portfolio/skip', name: 'freelancer_portfolio_skip', methods: ['POST'])]
    public function skipPortfolio(Request $request, UserRepository $userRepo): Response
    {
        $userId = $request->getSession()->get('pending_user_id');
        $user   = $userId ? $userRepo->find($userId) : null;

        if (!$user) return $this->redirectToRoute('user_signup');

        $this->finalizeLogin($request, $user);

        $this->addFlash('success', 'Welcome to UniEarn, ' . $user->getName() . '! You can add your portfolio later from your profile.');
        return $this->redirectToRoute('freelancer_dashboard');
    }

    // ── Helper ─────────────────────────────────────────────────────────

    private function finalizeLogin(Request $request, $user): void
    {
        $session = $request->getSession();
        $session->remove('pending_user_id');
        $session->remove('pending_freelancer_id');
        $session->set('user_id',   $user->getIdUser());
        $session->set('user_name', $user->getName());
        $session->set('user_role', $user->getRole());
    }
}