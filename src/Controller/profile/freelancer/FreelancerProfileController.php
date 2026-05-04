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
use App\Form\users\freelancer\FreelancerEditFormType;
use App\Form\users\freelancer\PortfolioItemFormType;


#[Route('/freelancer')]
class FreelancerProfileController extends AbstractController
{
    // ── DASHBOARD ──────────────────────────────────────────────────────

    #[Route('/dashboard', name: 'freelancer_dashboard', methods: ['GET'])]
    public function dashboard(
        Request                 $request,
        FreelancerRepository    $repo,
        PortfolioRepository     $portfolioRepo,
        PortfolioItemRepository $itemRepo
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('user_login');

        $freelancer = $repo->findByUserId($userId);
        if (!$freelancer) return $this->redirectToRoute('user_login');

        $portfolio = $portfolioRepo->findByFreelancerId($freelancer->getIdFreelancer());
        $items     = $portfolio ? $itemRepo->findByPortfolioId($portfolio->getIdPortfolio()) : [];

        return $this->render('frontOffice/freelancer/profile/dashboard.html.twig', [
            'freelancer'     => $freelancer,
            'user'           => $freelancer->getUser(),
            'viewerRole'     => 'FREELANCER',
            'backUrl'        => null,
            'portfolio'      => $portfolio,
            'items'          => $items,
            'sidebarInclude' => 'frontOffice/user/profile/_sidebar.html.twig',
            'sidebarActive'  => 'dashboard',
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

        // Pre-populate unmapped fields
        $form = $this->createForm(FreelancerEditFormType::class, $freelancer, [
            'data' => $freelancer,
        ]);
        $form->get('name')->setData($user->getName());
        $form->get('email')->setData($user->getEmail());
        $form->get('skillsInput')->setData($freelancer->getSkills());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name  = $form->get('name')->getData();
            $email = strtolower(trim($form->get('email')->getData()));

            // Email uniqueness check
            $existing = $userRepo->findByEmail($email);
            if ($existing && (int)$existing->getIdUser() !== (int)$user->getIdUser()) {
                $this->addFlash('error', 'This email is already used by another account.');
                return $this->render('frontOffice/freelancer/profile/edit-profile.html.twig', [
                    'form'       => $form->createView(),
                    'freelancer' => $freelancer,
                    'user'       => $user,
                ]);
            }

            // Password change
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword     = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if ($newPassword) {
                $dbPassword = $user->getPassword();
                $isMatch = false;

                // 1. Try hashed comparison
                if (str_starts_with($dbPassword, '$2')) {
                    if (password_verify($currentPassword, $dbPassword)) {
                        $isMatch = true;
                    }
                } 
                // 2. Fallback to plain text comparison
                if (!$isMatch && $dbPassword === $currentPassword) {
                    $isMatch = true;
                }

                if (!$isMatch) {
                    $this->addFlash('error', 'Current password is incorrect.');
                    return $this->render('frontOffice/freelancer/profile/edit-profile.html.twig', [
                        'form'       => $form->createView(),
                        'freelancer' => $freelancer,
                        'user'       => $user,
                    ]);
                }
                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'New passwords do not match.');
                    return $this->render('frontOffice/freelancer/profile/edit-profile.html.twig', [
                        'form'       => $form->createView(),
                        'freelancer' => $freelancer,
                        'user'       => $user,
                    ]);
                }
                $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
            }

            // Apply user fields
            $user->setName($name);
            $user->setEmail($email);

            // Apply skills from unmapped field
            $skillsRaw = $form->get('skillsInput')->getData();
            $skills    = array_values(array_filter(array_map('trim', explode(',', $skillsRaw))));
            $freelancer->setSkillsArray($skills);

            $em->flush();
            $request->getSession()->set('user_name', $name);

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('freelancer_dashboard');
        }

        return $this->render('frontOffice/freelancer/profile/edit-profile.html.twig', [
            'form'       => $form->createView(),
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

        $addForm = $this->createForm(PortfolioItemFormType::class);
        $editForm = $this->container->get('form.factory')->createNamed('edit_item', PortfolioItemFormType::class);

        return $this->render('frontOffice/freelancer/profile/portfolio-page.html.twig', [
            'freelancer' => $freelancer,
            'user'       => $freelancer->getUser(),
            'portfolio'  => $portfolio,
            'items'      => $items,
            'addForm'    => $addForm->createView(),
            'editForm'   => $editForm->createView(),
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
        PortfolioItemRepository $itemRepo,
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

        $item = new PortfolioItem();
        $form = $this->createForm(PortfolioItemFormType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item->setTechnologiesFromString($form->get('technologiesInput')->getData());
            $item->setImageUrl('[]');
            $item->setPortfolio($portfolio);
            $em->persist($item);
            $em->flush();
            $this->addFlash('success', 'Project added to portfolio!');
            return $this->redirectToRoute('freelancer_portfolio_page');
        }

        // Validation failed
        $editForm = $this->container->get('form.factory')->createNamed('edit_item', PortfolioItemFormType::class);
        return $this->render('frontOffice/freelancer/profile/portfolio-page.html.twig', [
            'freelancer'   => $freelancer,
            'user'         => $freelancer->getUser(),
            'portfolio'    => $portfolio,
            'items'        => $itemRepo->findByPortfolioId($portfolio->getIdPortfolio()),
            'addForm'      => $form->createView(),
            'editForm'     => $editForm->createView(),
            'openAddModal' => true,
        ]);
    }

    // ── EDIT PORTFOLIO ITEM ────────────────────────────────────────────

    #[Route('/portfolio/item/{id}/edit', name: 'freelancer_portfolio_item_edit', methods: ['POST'])]
    public function editItem(
        int                     $id,
        Request                 $request,
        PortfolioItemRepository $itemRepo,
        FreelancerRepository    $repo,
        PortfolioRepository     $portfolioRepo,
        EntityManagerInterface  $em
    ): Response {
        $item = $itemRepo->find($id);
        if (!$item) throw $this->createNotFoundException('Item not found.');

        $form = $this->container->get('form.factory')->createNamed('edit_item', PortfolioItemFormType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item->setTechnologiesFromString($form->get('technologiesInput')->getData());
            $em->flush();
            $this->addFlash('success', 'Project updated!');
            return $this->redirectToRoute('freelancer_portfolio_page');
        }

        // Validation failed
        $userId = $request->getSession()->get('user_id');
        $freelancer = $repo->findByUserId($userId);
        $portfolio  = $portfolioRepo->findByFreelancerId($freelancer->getIdFreelancer());
        
        $addForm = $this->createForm(PortfolioItemFormType::class);
        return $this->render('frontOffice/freelancer/profile/portfolio-page.html.twig', [
            'freelancer'    => $freelancer,
            'user'          => $freelancer->getUser(),
            'portfolio'     => $portfolio,
            'items'         => $portfolio ? $itemRepo->findByPortfolioId($portfolio->getIdPortfolio()) : [],
            'addForm'       => $addForm->createView(),
            'editForm'      => $form->createView(),
            'openEditModal' => $id,
        ]);
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