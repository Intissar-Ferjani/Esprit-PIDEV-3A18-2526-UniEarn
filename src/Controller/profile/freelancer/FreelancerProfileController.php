<?php

namespace App\Controller\profile\freelancer;

use App\Repository\users\freelancer\FreelancerRepository;
use App\Repository\users\freelancer\PortfolioRepository;
use App\Repository\users\freelancer\PortfolioItemRepository;
use App\Repository\users\user\UserRepository;
use App\Entity\users\freelancer\Portfolio;
use App\Entity\users\freelancer\PortfolioItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/freelancer')]
class FreelancerProfileController extends AbstractController
{
    // ── DASHBOARD ──────────────────────────────────────────────────────

    #[Route('/dashboard', name: 'freelancer_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, FreelancerRepository $repo): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $repo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        return $this->render('frontOffice/freelancer/profile/dashboard.html.twig', [
            'freelancer' => $freelancer,
            'user'       => $freelancer->getUser(),
        ]);
    }

    // ── EDIT PROFILE ───────────────────────────────────────────────────

    #[Route('/profile/edit', name: 'freelancer_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(
        Request                $request,
        FreelancerRepository   $repo,
        UserRepository         $userRepo,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $repo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $user = $freelancer->getUser();

        if ($request->isMethod('POST')) {
            $name         = trim($request->request->get('name', ''));
            $email        = strtolower(trim($request->request->get('email', '')));
            $pricePerHour = (float) $request->request->get('price_per_hour', 0);
            $skillsRaw    = trim($request->request->get('skills', ''));
            $bio          = trim($request->request->get('bio', ''));

            if (!$name || !$email) {
                $this->addFlash('error', 'Name and email are required.');
                return $this->redirectToRoute('freelancer_profile_edit');
            }

            // Email uniqueness check
            $existing = $userRepo->findByEmail($email);
            if ($existing && $existing->getIdUser() !== $user->getIdUser()) {
                $this->addFlash('error', 'This email is already used by another account.');
                return $this->redirectToRoute('freelancer_profile_edit');
            }

            // Password change
            $currentPassword = $request->request->get('current_password', '');
            $newPassword     = $request->request->get('new_password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            if ($newPassword !== '') {
                if ($user->getPassword() !== $currentPassword) {
                    $this->addFlash('error', 'Current password is incorrect.');
                    return $this->redirectToRoute('freelancer_profile_edit');
                }
                if (strlen($newPassword) < 8) {
                    $this->addFlash('error', 'New password must be at least 8 characters.');
                    return $this->redirectToRoute('freelancer_profile_edit');
                }
                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'New passwords do not match.');
                    return $this->redirectToRoute('freelancer_profile_edit');
                }
                $user->setPassword($newPassword);
            }

            $user->setName($name);
            $user->setEmail($email);
            $freelancer->setPricePerHour($pricePerHour);
            $freelancer->setBio($bio);

            // Skills
            $skills = array_values(array_filter(array_map('trim', explode(',', $skillsRaw))));
            $freelancer->setSkillsArray($skills);

            $em->flush();
            $request->getSession()->set('user_name', $name);

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('freelancer_dashboard');
        }

        return $this->render('frontOffice/freelancer/profile/edit-profile.html.twig', [
            'freelancer' => $freelancer,
            'user'       => $user,
        ]);
    }

    // ── CHANGE PHOTO ───────────────────────────────────────────────────

    #[Route('/profile/photo', name: 'freelancer_change_photo', methods: ['POST'])]
    public function changePhoto(
        Request                $request,
        FreelancerRepository   $repo,
        EntityManagerInterface $em,
        SluggerInterface       $slugger
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $repo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $photoFile = $request->files->get('profile_photo');
        if ($photoFile) {
            $newFilename = 'freelancer_' . $userId . '_' . uniqid() . '.' . $photoFile->guessExtension();
            try {
                $photoFile->move($this->getParameter('profile_pictures_directory'), $newFilename);
                $freelancer->getUser()->setProfilePicturePath('uploads/profiles/' . $newFilename);
                $em->flush();
                $this->addFlash('success', 'Profile photo updated!');
            } catch (FileException) {
                $this->addFlash('error', 'Could not upload photo.');
            }
        }

        return $this->redirectToRoute('freelancer_dashboard');
    }

    // ── DEACTIVATE ─────────────────────────────────────────────────────

    #[Route('/profile/deactivate', name: 'freelancer_deactivate', methods: ['POST'])]
    public function deactivate(
        Request                $request,
        FreelancerRepository   $repo,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $confirm = $request->request->get('confirm_text', '');
        if (strtoupper($confirm) !== 'DEACTIVATE') {
            $this->addFlash('error', 'You must type DEACTIVATE to confirm.');
            return $this->redirectToRoute('freelancer_profile_edit');
        }

        $freelancer = $repo->findByUserId($userId);
        if ($freelancer) {
            $freelancer->getUser()->setActivated(false);
            $em->flush();
        }

        $request->getSession()->invalidate();
        return $this->redirectToRoute('user_login');
    }

    // ── PORTFOLIO ──────────────────────────────────────────────────────

    #[Route('/portfolio', name: 'freelancer_portfolio_page', methods: ['GET'])]
    public function portfolioPage(
        Request              $request,
        FreelancerRepository $repo,
        PortfolioRepository  $portfolioRepo,
        PortfolioItemRepository $itemRepo
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $repo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $portfolio = $portfolioRepo->findByFreelancerId($freelancer->getIdFreelancer());
        $items     = $portfolio ? $itemRepo->findByPortfolioId($portfolio->getIdPortfolio()) : [];

        return $this->render('frontOffice/freelancer/profile/portfolio-page.html.twig', [
            'freelancer' => $freelancer,
            'user'       => $freelancer->getUser(),
            'portfolio'  => $portfolio,
            'items'      => $items,
        ]);
    }

    // ── CREATE PORTFOLIO ───────────────────────────────────────────────

    #[Route('/portfolio/create', name: 'freelancer_portfolio_create', methods: ['POST'])]
    public function createPortfolio(
        Request                $request,
        FreelancerRepository   $repo,
        PortfolioRepository    $portfolioRepo,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $repo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        // Don't create if one already exists
        if (!$portfolioRepo->findByFreelancerId($freelancer->getIdFreelancer())) {
            $portfolio = new Portfolio();
            $portfolio->setTitle($freelancer->getName() . "'s Portfolio");
            $portfolio->setDescription('My professional portfolio');
            $portfolio->setFreelancer($freelancer);
            $em->persist($portfolio);
            $em->flush();
            $this->addFlash('success', 'Portfolio created!');
        }

        return $this->redirectToRoute('freelancer_portfolio_page');
    }

    // ── ADD PORTFOLIO ITEM ─────────────────────────────────────────────

    #[Route('/portfolio/item/add', name: 'freelancer_portfolio_item_add', methods: ['POST'])]
    public function addItem(
        Request                 $request,
        FreelancerRepository    $repo,
        PortfolioRepository     $portfolioRepo,
        EntityManagerInterface  $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $repo->findByUserId($userId);
        $portfolio  = $portfolioRepo->findByFreelancerId($freelancer->getIdFreelancer());

        if (!$portfolio) {
            $this->addFlash('error', 'Please create a portfolio first.');
            return $this->redirectToRoute('freelancer_portfolio_page');
        }

        $title = trim($request->request->get('title', ''));
        $desc  = trim($request->request->get('description', ''));

        if (!$title || !$desc) {
            $this->addFlash('error', 'Title and description are required.');
            return $this->redirectToRoute('freelancer_portfolio_page');
        }

        $item = new PortfolioItem();
        $item->setTitle($title);
        $item->setDescription($desc);
        $item->setTechnologiesFromString($request->request->get('technologies', ''));
        $item->setProjectUrl($request->request->get('project_url') ?: null);
        $item->setGithubUrl($request->request->get('github_url') ?: null);
        $item->setImageUrl('[]');
        $item->setPortfolio($portfolio);

        $em->persist($item);
        $em->flush();

        $this->addFlash('success', 'Project added to portfolio!');
        return $this->redirectToRoute('freelancer_portfolio_page');
    }

    // ── EDIT PORTFOLIO ITEM ────────────────────────────────────────────

    #[Route('/portfolio/item/{id}/edit', name: 'freelancer_portfolio_item_edit', methods: ['POST'])]
    public function editItem(
        int                     $id,
        Request                 $request,
        PortfolioItemRepository $itemRepo,
        EntityManagerInterface  $em
    ): Response {
        $item = $itemRepo->find($id);
        if (!$item) throw $this->createNotFoundException('Item not found.');

        $item->setTitle(trim($request->request->get('title', '')));
        $item->setDescription(trim($request->request->get('description', '')));
        $item->setTechnologiesFromString($request->request->get('technologies', ''));
        $item->setProjectUrl($request->request->get('project_url') ?: null);
        $item->setGithubUrl($request->request->get('github_url') ?: null);
        $em->flush();

        $this->addFlash('success', 'Project updated!');
        return $this->redirectToRoute('freelancer_portfolio_page');
    }

    // ── DELETE PORTFOLIO ITEM ──────────────────────────────────────────

    #[Route('/portfolio/item/{id}/delete', name: 'freelancer_portfolio_item_delete', methods: ['POST'])]
    public function deleteItem(
        int                     $id,
        PortfolioItemRepository $itemRepo,
        EntityManagerInterface  $em
    ): Response {
        $item = $itemRepo->find($id);
        if ($item) { $em->remove($item); $em->flush(); }
        $this->addFlash('success', 'Project deleted.');
        return $this->redirectToRoute('freelancer_portfolio_page');
    }

    // ── EDIT PORTFOLIO INFO ────────────────────────────────────────────

    #[Route('/portfolio/edit-info', name: 'freelancer_portfolio_edit_info', methods: ['POST'])]
    public function editPortfolioInfo(
        Request                $request,
        FreelancerRepository   $repo,
        PortfolioRepository    $portfolioRepo,
        EntityManagerInterface $em
    ): Response {
        $userId     = $request->getSession()->get('user_id');
        $freelancer = $repo->findByUserId($userId);
        $portfolio  = $portfolioRepo->findByFreelancerId($freelancer->getIdFreelancer());

        if ($portfolio) {
            $portfolio->setTitle(trim($request->request->get('title', $portfolio->getTitle())));
            $portfolio->setDescription(trim($request->request->get('description', '')));
            $em->flush();
            $this->addFlash('success', 'Portfolio info updated!');
        }

        return $this->redirectToRoute('freelancer_portfolio_page');
    }

    // ── DELETE PORTFOLIO ───────────────────────────────────────────────

    #[Route('/portfolio/delete', name: 'freelancer_portfolio_delete', methods: ['POST'])]
    public function deletePortfolio(
        Request                $request,
        FreelancerRepository   $repo,
        PortfolioRepository    $portfolioRepo,
        EntityManagerInterface $em
    ): Response {
        $confirm = $request->request->get('confirm_text', '');
        if (strtoupper($confirm) !== 'DELETE') {
            $this->addFlash('error', "You must type DELETE to confirm.");
            return $this->redirectToRoute('freelancer_portfolio_page');
        }

        $userId     = $request->getSession()->get('user_id');
        $freelancer = $repo->findByUserId($userId);
        $portfolio  = $portfolioRepo->findByFreelancerId($freelancer->getIdFreelancer());

        if ($portfolio) { $em->remove($portfolio); $em->flush(); }
        $this->addFlash('success', 'Portfolio deleted.');
        return $this->redirectToRoute('freelancer_dashboard');
    }
}