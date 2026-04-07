<?php

namespace App\Controller\contract\client;

use App\Entity\contract\Contract;
use App\Form\contract\ContractFormType;
use App\Repository\contract\ContractRepository;
use App\Repository\contract\ContractTemplateRepository;
use App\Repository\users\client\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/client/contracts')]
class ClientContractController extends AbstractController
{
    private function requireClient(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'CLIENT') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    // ── LIST CLIENT CONTRACTS ───────────────────────────────────────────

    #[Route('/', name: 'client_contract_index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $repo, ClientRepository $clientRepo): Response
    {
        if ($r = $this->requireClient($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        if (!$client) throw $this->createNotFoundException('Client not found.');

        $status = $request->query->get('status');
        $validStatuses = ['pending', 'signed'];
        if ($status && !in_array($status, $validStatuses, true)) {
            $status = null;
        }

        return $this->render('frontOffice/client/contract/list-contracts.html.twig', [
            'contracts'     => $repo->findByClientId($client->getIdClient(), $status),
            'currentStatus' => $status,
        ]);
    }

    // ── CREATE CONTRACT FROM TEMPLATE ────────────────────────────────────

    #[Route('/new/{templateId}', name: 'client_contract_new', methods: ['GET', 'POST'])]
    public function new(int $templateId, Request $request, ContractTemplateRepository $templateRepo, ClientRepository $clientRepo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireClient($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        if (!$client) throw $this->createNotFoundException('Client not found.');

        $template = $templateRepo->find($templateId);
        if (!$template) throw $this->createNotFoundException('Template not found.');

        $contract = new Contract();
        $contract->setTemplate($template);
        $contract->setClient($client);
        $contract->setContent($template->getContent());
        $contract->setTitle($template->getTitle());

        $form = $this->createForm(ContractFormType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract->setStatus('pending');
            $em->persist($contract);
            $em->flush();
            $this->addFlash('success', 'Contract created successfully.');
            return $this->redirectToRoute('client_contract_index');
        }

        return $this->render('frontOffice/client/contract/new-contract.html.twig', [
            'form'     => $form->createView(),
            'template' => $template,
        ]);
    }

    // ── CHOOSE TEMPLATE ─────────────────────────────────────────────────

    #[Route('/templates', name: 'client_contract_templates', methods: ['GET'])]
    public function templates(Request $request, ContractTemplateRepository $repo): Response
    {
        if ($r = $this->requireClient($request)) return $r;

        return $this->render('frontOffice/client/contract/choose-template.html.twig', [
            'templates' => $repo->findAllOrderedByDate(),
        ]);
    }

    // ── VIEW CONTRACT ───────────────────────────────────────────────────

    #[Route('/{id}', name: 'client_contract_show', methods: ['GET'])]
    public function show(int $id, Request $request, ContractRepository $repo, ClientRepository $clientRepo): Response
    {
        if ($r = $this->requireClient($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getClient()->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        return $this->render('frontOffice/client/contract/show-contract.html.twig', [
            'contract' => $contract,
        ]);
    }

    // ── SIGN CONTRACT (CLIENT) ──────────────────────────────────────────

    #[Route('/{id}/sign', name: 'client_contract_sign', methods: ['POST'])]
    public function sign(int $id, Request $request, ContractRepository $repo, ClientRepository $clientRepo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireClient($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getClient()->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        $signatureData = $request->request->get('signature');
        if (!$signatureData || !str_starts_with($signatureData, 'data:image/png;base64,')) {
            $this->addFlash('error', 'Please draw your signature before submitting.');
            return $this->redirectToRoute('client_contract_show', ['id' => $id]);
        }

        $contract->setClientSignature(new \DateTime());
        $contract->setClientSignatureImage($signatureData);
        $contract->setUpdatedAt(new \DateTime());

        if ($contract->getFreelancerSignature() !== null) {
            $contract->setStatus('signed');
        }

        $em->flush();
        $this->addFlash('success', 'You have signed the contract.');

        return $this->redirectToRoute('client_contract_show', ['id' => $id]);
    }

    // ── EXPORT PDF ──────────────────────────────────────────────────────

    #[Route('/{id}/pdf', name: 'client_contract_pdf', methods: ['GET'])]
    public function pdf(int $id, Request $request, ContractRepository $repo, ClientRepository $clientRepo): Response
    {
        if ($r = $this->requireClient($request)) return $r;

        $userId = $request->getSession()->get('user_id');
        $client = $clientRepo->findByUserId($userId);
        $contract = $repo->find($id);

        if (!$contract || $contract->getClient()->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Contract not found.');
        }

        if (!$contract->isSigned()) {
            $this->addFlash('error', 'Contract must be signed by both parties before exporting.');
            return $this->redirectToRoute('client_contract_show', ['id' => $id]);
        }

        return $this->render('frontOffice/contract/pdf-contract.html.twig', [
            'contract' => $contract,
        ]);
    }
}
