<?php

namespace App\Controller\contract\freelancer;

use App\Repository\contract\ContractRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/freelancer/payments', name: 'freelancer_payments_')]
class FreelancerPaymentController extends AbstractController
{
    private function requireFreelancer(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'FREELANCER') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) throw $this->createNotFoundException('Freelancer not found.');

        // On récupère les contrats qui sont soit payés (funded) soit terminés (completed)
        // Note: 'released' semble être utilisé ailleurs, on l'inclut par sécurité
        $allPaymentContracts = $repo->findByFreelancerIdAndStatuses(
            $freelancer->getIdFreelancer(),
            ['funded', 'completed', 'released']
        );

        $blocked = array_filter($allPaymentContracts, fn($c) => $c->getStatus() === 'funded');
        $received = array_filter($allPaymentContracts, fn($c) => in_array($c->getStatus(), ['completed', 'released'], true));

        // Calcul des totaux
        $totalBlocked = 0;
        foreach ($blocked as $c) $totalBlocked += $c->getAmount();

        $totalReceived = 0;
        foreach ($received as $c) $totalReceived += $c->getAmount();

        return $this->render('frontOffice/freelancer/payments/index.html.twig', [
            'blockedContracts' => $blocked,
            'receivedContracts' => $received,
            'totalBlocked' => $totalBlocked,
            'totalReceived' => $totalReceived,
        ]);
    }
}