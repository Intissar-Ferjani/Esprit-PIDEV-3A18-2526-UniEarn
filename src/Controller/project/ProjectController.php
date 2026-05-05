<?php

namespace App\Controller\project;

use App\Entity\project\Project;
use App\Enum\TaskStatus;
use App\Form\project\ProjectType;
use App\Repository\project\ProjectRepository;
use App\Repository\task\TaskRepository;
use App\Repository\users\client\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/client/projects')]
final class ProjectController extends AbstractController
{
    #[Route('/', name: 'client_project_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $client = $clientRepository->findByUserId($userId);
        if (!$client) {
            $this->addFlash('error', 'Only clients can manage projects.');
            return $this->redirectToRoute('client_dashboard');
        }

        $search = trim((string) $request->query->get('search', ''));
        /** @var array<int, Project> $projects */
        $projects = $projectRepository->findByClient((int) $client->getIdClient());
        if ($search !== '') {
            $projects = array_values(array_filter($projects, static fn (Project $project): bool => str_contains(strtolower($project->getTitle() ?? ''), strtolower($search))));
        }

        $project = new Project();
        $project->setClient($client);
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($project);
            $entityManager->flush();
            $this->addFlash('success', 'Project created successfully.');

            return $this->redirectToRoute('client_project_index', [], Response::HTTP_SEE_OTHER);
        }

        /** @var array<int, \App\Entity\task\Task> $allTasks */
        $allTasks = count($projects) ? $taskRepository->findBy(['project' => $projects]) : [];
        $progress = $taskRepository->getProjectStats($projects, $allTasks)['progress'];
        $reviewTasks = array_values(array_filter($allTasks, static fn($t) => $t->getTaskStatus() === TaskStatus::REVIEW));
        usort($reviewTasks, static fn($a, $b) => $b->getIdTask() <=> $a->getIdTask());

        return $this->render('frontOffice/client/project/projet.html.twig', [
            'projects' => $projects,
            'project_progress' => $progress,
            'review_tasks' => $reviewTasks,
            'project' => $project,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'client_project_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $client = $clientRepository->findByUserId($userId);
        if (!$client) {
            $this->addFlash('error', 'Only clients can manage projects.');
            return $this->redirectToRoute('client_dashboard');
        }

        $project = $projectRepository->find($id);
        if (!$project instanceof Project || $project->getClient()?->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Project not found.');
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Project updated successfully.');

            return $this->redirectToRoute('client_project_index', [], Response::HTTP_SEE_OTHER);
        }

        $search = trim((string) $request->query->get('search', ''));
        /** @var array<int, Project> $projects */
        $projects = $projectRepository->findByClient((int) $client->getIdClient());
        if ($search !== '') {
            $projects = array_values(array_filter($projects, static fn (Project $item): bool => str_contains(strtolower($item->getTitle() ?? ''), strtolower($search))));
        }

        /** @var array<int, \App\Entity\task\Task> $allTasks */
        $allTasks = count($projects) ? $taskRepository->findBy(['project' => $projects]) : [];
        $progress = $taskRepository->getProjectStats($projects, $allTasks)['progress'];
        $reviewTasks = array_values(array_filter($allTasks, static fn($t) => $t->getTaskStatus() === TaskStatus::REVIEW));
        usort($reviewTasks, static fn($a, $b) => $b->getIdTask() <=> $a->getIdTask());

        return $this->render('frontOffice/client/project/projet.html.twig', [
            'projects' => $projects,
            'project_progress' => $progress,
            'review_tasks' => $reviewTasks,
            'project' => $project,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}', name: 'client_project_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        ProjectRepository $projectRepository,
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $client = $clientRepository->findByUserId($userId);
        if (!$client) {
            $this->addFlash('error', 'Only clients can manage projects.');
            return $this->redirectToRoute('client_dashboard');
        }

        $project = $projectRepository->find($id);
        if (!$project instanceof Project || $project->getClient()?->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Project not found.');
        }

        if ($this->isCsrfTokenValid('delete'.$project->getIdProject(), (string) $request->request->get('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();
            $this->addFlash('success', 'Project deleted successfully.');
        }

        return $this->redirectToRoute('client_project_index', [], Response::HTTP_SEE_OTHER);
    }
}
