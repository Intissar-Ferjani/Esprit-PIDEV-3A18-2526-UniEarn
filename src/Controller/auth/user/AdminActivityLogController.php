<?php

namespace App\Controller\auth\user;

use App\Repository\log\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminActivityLogController extends AbstractController
{
    #[Route('/activity', name: 'admin_activity_log', methods: ['GET'])]
    public function index(Request $request, ActivityLogRepository $logRepository): Response
    {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        $adminName = $request->getSession()->get('user_name', 'Admin');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        $logs = $logRepository->findRecentLogs(100);

        return $this->render('backoffice/activity/index.html.twig', [
            'adminName' => $adminName,
            'logs' => $logs
        ]);
    }
}
