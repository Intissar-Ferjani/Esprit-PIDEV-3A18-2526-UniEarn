<?php

namespace App\Controller\contract\admin;

use App\Entity\contract\Contract;
use App\Repository\contract\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/escrow')]
class AdminEscrowController extends AbstractController
{
    private function requireAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    #[Route('/', name: 'admin_escrow_index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $repo): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $contracts = $repo->findAllOrderedByDate();
        
        $fundedContracts = [];
        $totalEscrow = 0;
        
        foreach ($contracts as $c) {
            if ($c->getStatus() === 'funded') {
                $fundedContracts[] = $c;
                $totalEscrow += $c->getAmount();
            }
        }

        return $this->render('backOffice/contract/escrow-manager.html.twig', [
            'fundedContracts' => $fundedContracts,
            'totalEscrow' => $totalEscrow,
        ]);
    }

    #[Route('/{id}/release', name: 'admin_escrow_release', methods: ['POST'])]
    public function releaseFunds(int $id, Request $request, ContractRepository $repo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $contract = $repo->find($id);
        if (!$contract) {
            throw $this->createNotFoundException('Contract not found.');
        }

        if ($contract->getStatus() !== 'funded') {
            $this->addFlash('error', 'Ces fonds ne sont pas en séquestre ou ont déjà été libérés.');
            return $this->redirectToRoute('admin_escrow_index');
        }

        $freelancer = $contract->getFreelancer();
        if (!$freelancer->getIban()) {
            $this->addFlash('error', 'Impossible de transférer : le Freelancer n\'a pas configuré son RIB/IBAN.');
            return $this->redirectToRoute('admin_escrow_index');
        }

        // SIMULATION VRAI SYSTÈME : 
        // Ici on appellerait Stripe Payouts API pour envoyer l'argent vers $freelancer->getIban()
        
        $contract->setStatus('completed');
        $em->flush();

        $this->addFlash('success', '✅ Fonds libérés ! $' . $contract->getAmount() . ' ont été (virtuellement) transférés vers l\'IBAN : ' . ltrim($freelancer->getIban()));

        return $this->redirectToRoute('admin_escrow_index');
    }
}
