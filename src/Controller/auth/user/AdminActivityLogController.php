<?php

namespace App\Controller\auth\user;

use App\Repository\log\ActivityLogRepository;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
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

        $filterUserId = $request->query->get('user_id');
        $filterAction = $request->query->get('action_type');
        
        $logs = $logRepository->findRecentLogs(
            100, 
            $filterUserId ? (int)$filterUserId : null,
            $filterAction
        );

        return $this->render('backoffice/activity/index.html.twig', [
            'adminName' => $adminName,
            'logs' => $logs
        ]);
    }

    /**
     * Advanced Feature: PDF Export
     * This logic intercepts the normal web view response, renders the Twig template into pure HTML,
     * and uses the KnpSnappyBundle (wkhtmltopdf binary underneath) to render a downloadable PDF.
     */
    #[Route('/activity/export', name: 'admin_activity_log_export', methods: ['GET'])]
    public function exportPdf(Request $request, ActivityLogRepository $logRepository, Pdf $snappy): Response
    {
        $userId   = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId || $userRole !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }

        $filterUserId = $request->query->get('user_id');
        $filterAction = $request->query->get('action_type');

        // Fetch up to 500 logs for the full report to prevent memory exhaustion on massive databases.
        $logs = $logRepository->findRecentLogs(
            500, 
            $filterUserId ? (int)$filterUserId : null,
            $filterAction
        );

        // Render the strictly structured pdf twig template into a string of HTML
        $html = $this->renderView('backoffice/activity/report_pdf.html.twig', [
            'logs' => $logs,
            'date' => new \DateTime(),
            'filterUserId' => $filterUserId,
            'filterAction' => $filterAction
        ]);

        // Pipeline the HTML string into the snappy PDF engine and return as a direct downloadable file stream.
        return new PdfResponse(
            $snappy->getOutputFromHtml($html),
            'activity_log_report_' . date('Y-m-d_His') . '.pdf'
        );
    }
}
