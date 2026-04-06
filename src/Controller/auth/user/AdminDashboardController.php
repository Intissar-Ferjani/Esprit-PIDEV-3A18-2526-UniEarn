<?php

namespace App\Controller\auth\user;

use App\Repository\users\user\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepo): Response
    {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        $adminName = $request->getSession()->get('user_name', 'Admin');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        // Calculate statistics for the dashboard
        $allUsers = $userRepo->findAllUsers();
        $active   = $userRepo->findAllActiveUsers();
        
        $totalUsers = count($allUsers);
        $totalActive = count($active);
        
        // Split by role
        $clients = 0;
        $freelancers = 0;
        foreach ($allUsers as $u) {
            if ($u->getRole() === 'CLIENT') $clients++;
            if ($u->getRole() === 'FREELANCER') $freelancers++;
        }

        $activePercent = ($totalUsers > 0) ? round(($totalActive / $totalUsers) * 100) : 0;

        return $this->render('backoffice/dashboard.html.twig', [
            'adminName' => $adminName,
            'stats' => [
                'total'         => $totalUsers,
                'active'        => $totalActive,
                'activePercent' => $activePercent,
                'clients'       => $clients,
                'freelancers'   => $freelancers,
            ]
        ]);
    }
}
