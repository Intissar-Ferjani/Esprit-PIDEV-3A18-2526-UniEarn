<?php

namespace App\Controller\auth\freelancer;

use App\Entity\users\freelancer\Freelancer;
use App\Entity\users\freelancer\Portfolio;
use App\Entity\users\freelancer\PortfolioItem;
use App\Form\users\freelancer\FreelancerProfileFormType;
use App\Form\users\freelancer\PortfolioFormType;
use App\Form\users\freelancer\StudentCardFormType;
use App\Repository\users\freelancer\FreelancerRepository;
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
        SluggerInterface       $slugger
    ): Response {
        $userId = $request->getSession()->get('pending_user_id');
        if (!$userId) return $this->redirectToRoute('user_signup');

        $user = $userRepo->find($userId);
        if (!$user || $user->getRole() !== 'FREELANCER') return $this->redirectToRoute('user_signup');

        $freelancer = new Freelancer();
        $form       = $this->createForm(FreelancerProfileFormType::class, $freelancer);        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Skills — from comma-separated hidden input
            $skillsRaw = $form->get('skillsInput')->getData();
            $skills    = array_values(array_filter(array_map('trim', explode(',', $skillsRaw ?? ''))));

            if (empty($skills)) {
                $this->addFlash('error', 'Please add at least one skill.');
                return $this->render('frontOffice/freelancer/auth/freelancer-information.html.twig', ['form' => $form, 'user' => $user]);
            }
            $freelancer->setSkillsArray($skills);

            // CV upload
            $cvFile = $form->get('cvFile')->getData();
            if ($cvFile) {
                $newFilename = uniqid() . '_' . $slugger->slug(pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf';
                try {
                    $cvFile->move($this->getParameter('cv_directory'), $newFilename);
                    $freelancer->setCvPath('uploads/cv/' . $newFilename);
                } catch (FileException) {
                    $this->addFlash('error', 'Could not upload CV.');
                    return $this->render('frontOffice/freelancer/auth/freelancer-information.html.twig', ['form' => $form, 'user' => $user]);
                }
            }

            $freelancer->setUser($user);
            $freelancer->setAmount(0.0);
            $freelancer->setRating(0.0);
            $freelancer->setVerificationStatus('unverified');
            $freelancer->setStatus('available');
            $freelancer->setIdTask(null);

            $em->persist($freelancer);
            $em->flush();

            // Store freelancer ID in session for next steps
            $request->getSession()->set('pending_freelancer_id', $freelancer->getIdFreelancer());

            return $this->redirectToRoute('freelancer_verify');
        }

        return $this->render('frontOffice/freelancer/auth/freelancer-information.html.twig', ['form' => $form, 'user' => $user]);
    }

    // ── STEP 3 — Student Card Verification ────────────────────────────

    #[Route('/verify', name: 'freelancer_verify', methods: ['GET', 'POST'])]
    public function verify(
        Request                $request,
        EntityManagerInterface $em,
        UserRepository         $userRepo,
        FreelancerRepository   $freelancerRepo,
        SluggerInterface       $slugger
    ): Response {
        $userId       = $request->getSession()->get('pending_user_id');
        $freelancerId = $request->getSession()->get('pending_freelancer_id');

        if (!$userId || !$freelancerId) return $this->redirectToRoute('user_signup');

        $user       = $userRepo->find($userId);
        $freelancer = $freelancerRepo->find($freelancerId);

        if (!$user || !$freelancer) return $this->redirectToRoute('user_signup');

        $form = $this->createForm(StudentCardFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cardFile = $form->get('studentCardFile')->getData();

            if ($cardFile) {
                $ext         = $cardFile->guessExtension();
                $newFilename = 'card_' . $userId . '_' . uniqid() . '.' . $ext;
                try {
                    $cardFile->move($this->getParameter('student_cards_directory'), $newFilename);
                    $freelancer->setStudentCardPath('uploads/student_cards/' . $newFilename);
                    $em->flush();
                } catch (FileException) {
                    $this->addFlash('error', 'Could not upload student card.');
                    return $this->render('frontOffice/freelancer/auth/student-id-verification.html.twig', ['form' => $form, 'user' => $user]);
                }
            }

            return $this->redirectToRoute('freelancer_portfolio');
        }

        return $this->render('frontOffice/freelancer/auth/student-id-verification.html.twig', ['form' => $form, 'user' => $user]);
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