<?php

namespace App\Controller\contract\admin;

use App\Entity\contract\ContractTemplate;
use App\Form\contract\ContractTemplateFormType;
use App\Repository\contract\ContractTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/contract-templates')]
class AdminContractTemplateController extends AbstractController
{
    private function requireAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('user_login');
        }
        return null;
    }

    // ── LIST ────────────────────────────────────────────────────────────

    #[Route('/', name: 'admin_contract_template_index', methods: ['GET'])]
    public function index(Request $request, ContractTemplateRepository $repo): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        return $this->render('backOffice/contract/list-templates.html.twig', [
            'templates' => $repo->findAllOrderedByDate(),
        ]);
    }

    // ── CREATE ──────────────────────────────────────────────────────────

    #[Route('/new', name: 'admin_contract_template_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $template = new ContractTemplate();
        $form = $this->createForm(ContractTemplateFormType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($template);
            $em->flush();
            $this->addFlash('success', 'Contract template created successfully.');
            return $this->redirectToRoute('admin_contract_template_index');
        }

        return $this->render('backOffice/contract/new-template.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ── EDIT ────────────────────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'admin_contract_template_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, ContractTemplateRepository $repo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $template = $repo->find($id);
        if (!$template) throw $this->createNotFoundException('Template not found.');

        $form = $this->createForm(ContractTemplateFormType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $template->setUpdatedAt(new \DateTime());
            $em->flush();
            $this->addFlash('success', 'Contract template updated successfully.');
            return $this->redirectToRoute('admin_contract_template_index');
        }

        return $this->render('backOffice/contract/edit-template.html.twig', [
            'form'     => $form->createView(),
            'template' => $template,
        ]);
    }

    // ── VIEW ────────────────────────────────────────────────────────────

    #[Route('/{id}', name: 'admin_contract_template_show', methods: ['GET'])]
    public function show(int $id, Request $request, ContractTemplateRepository $repo): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $template = $repo->find($id);
        if (!$template) throw $this->createNotFoundException('Template not found.');

        return $this->render('backOffice/contract/show-template.html.twig', [
            'template' => $template,
        ]);
    }

    // ── DELETE ───────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'admin_contract_template_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, ContractTemplateRepository $repo, EntityManagerInterface $em): Response
    {
        if ($r = $this->requireAdmin($request)) return $r;

        $template = $repo->find($id);
        if (!$template) throw $this->createNotFoundException('Template not found.');

        // Check if contracts reference this template
        $contractCount = $em->getRepository(\App\Entity\contract\Contract::class)
            ->count(['template' => $template]);

        if ($contractCount > 0) {
            $this->addFlash('error', "Cannot delete this template: $contractCount contract(s) are still using it.");
            return $this->redirectToRoute('admin_contract_template_index');
        }

        $em->remove($template);
        $em->flush();
        $this->addFlash('success', 'Contract template deleted successfully.');

        return $this->redirectToRoute('admin_contract_template_index');
    }
}
