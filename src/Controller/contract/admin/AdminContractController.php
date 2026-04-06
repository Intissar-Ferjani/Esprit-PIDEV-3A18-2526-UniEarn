<?php

namespace App\Controller\contract\admin;

use App\Entity\contract\Contract;
use App\Repository\contract\ContractRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/contracts')]
class AdminContractController extends AbstractController
{
    private function requireAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    // ── LIST ALL CONTRACTS ──────────────────────────────────────────────

    #[Route('/', name: 'admin_contract_index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $repo): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        return $this->render('backOffice/contract/list-contracts.html.twig', [
            'contracts' => $repo->findAllOrderedByDate(),
        ]);
    }

    // ── VIEW ONE CONTRACT ───────────────────────────────────────────────

    #[Route('/{id}', name: 'admin_contract_show', methods: ['GET'])]
    public function show(int $id, Request $request, ContractRepository $repo): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $contract = $repo->find($id);
        if (!$contract) {
            throw $this->createNotFoundException('Contract not found.');
        }

        return $this->render('backOffice/contract/show-contract.html.twig', [
            'contract' => $contract,
        ]);
    }
}
