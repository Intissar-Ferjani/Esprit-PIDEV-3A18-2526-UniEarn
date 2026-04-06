<?php

namespace App\Controller\contract\freelancer;

use App\Repository\contract\ContractRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/freelancer/contracts')]
class FreelancerContractController extends AbstractController
{
    private function requireFreelancer(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'FREELANCER') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    // ── LIST FREELANCER CONTRACTS ───────────────────────────────────────

    #[Route('/', name: 'freelancer_contract_index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        if (!$freelancer) throw $this->createNotFoundException('Freelancer not found.');

        $status = $request->query->get('status');
        $validStatuses = ['pending', 'signed'];
        if ($status && !in_array($status, $validStatuses, true)) {
            $status = null;
        }

        return $this->render('frontOffice/freelancer/contract/list-contracts.html.twig', [
            'contracts'     => $repo->findByFreelancerId($freelancer->getIdFreelancer(), $status),
            'currentStatus' => $status,
        ]);
    }

    // ── VIEW CONTRACT ───────────────────────────────────────────────────

    #[Route('/{id}', name: 'freelancer_contract_show', methods: ['GET'])]
    public function show(int $id, Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getFreelancer()->getIdFreelancer() !== $freelancer->getIdFreelancer()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        return $this->render('frontOffice/freelancer/contract/show-contract.html.twig', [
            'contract' => $contract,
        ]);
    }

    // ── SIGN CONTRACT (FREELANCER) ──────────────────────────────────────

    #[Route('/{id}/sign', name: 'freelancer_contract_sign', methods: ['POST'])]
    public function sign(int $id, Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getFreelancer()->getIdFreelancer() !== $freelancer->getIdFreelancer()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        $signatureData = $request->request->get('signature');
        if (!$signatureData || !str_starts_with($signatureData, 'data:image/png;base64,')) {
            $this->addFlash('error', 'Please draw your signature before submitting.');
            return $this->redirectToRoute('freelancer_contract_show', ['id' => $id]);
        }

        $contract->setFreelancerSignature(new \DateTime());
        $contract->setFreelancerSignatureImage($signatureData);
        $contract->setUpdatedAt(new \DateTime());

        if ($contract->getClientSignature() !== null) {
            $contract->setStatus('signed');
        }

        $em->flush();
        $this->addFlash('success', 'You have signed the contract.');

        return $this->redirectToRoute('freelancer_contract_show', ['id' => $id]);
    }

    // ── EXPORT PDF ──────────────────────────────────────────────────────

    #[Route('/{id}/pdf', name: 'freelancer_contract_pdf', methods: ['GET'])]
    public function pdf(int $id, Request $request, ContractRepository $repo, FreelancerRepository $freelancerRepo): Response
    {
        if ($r = $this->requireFreelancer($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $freelancer = $freelancerRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getFreelancer()->getIdFreelancer() !== $freelancer->getIdFreelancer()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        if (!$contract->isSigned()) {
            $this->addFlash('error', 'Contract must be signed by both parties before exporting.');
            return $this->redirectToRoute('freelancer_contract_show', ['id' => $id]);
        }

        return $this->render('frontOffice/contract/pdf-contract.html.twig', [
            'contract' => $contract,
        ]);
    }
}
