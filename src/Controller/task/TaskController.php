<?php

namespace App\Controller\task;

use App\Entity\task\Task;
use App\Form\task\TaskType;
use App\Repository\candidature\ApplicationRepository;
use App\Repository\project\ProjectRepository;
use App\Repository\task\TaskRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/freelancer/tasks')]
final class TaskController extends AbstractController
{
    #[Route('/', name: 'freelancer_task_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        ApplicationRepository $applicationRepository,
        FreelancerRepository $freelancerRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $freelancer = $freelancerRepository->findByUserId($userId);
        if (!$freelancer) {
            $this->addFlash('error', 'Only freelancers can manage tasks.');
            return $this->redirectToRoute('freelancer_dashboard');
        }

        $assignedProjects = $projectRepository->findByFreelancer($freelancer->getIdFreelancer());
        $acceptedProjectIds = $applicationRepository->findAcceptedProjectIdsForFreelancer($freelancer->getIdFreelancer());
        $acceptedProjects = $projectRepository->findByIds($acceptedProjectIds);
        $projectMap = [];
        foreach (array_merge($assignedProjects, $acceptedProjects) as $project) {
            $projectMap[$project->getIdProject()] = $project;
        }
        $projects = array_values($projectMap);
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task, ['projects' => $projects]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();
            $this->addFlash('success', 'Task created successfully.');

            return $this->redirectToRoute('freelancer_task_index', [], Response::HTTP_SEE_OTHER);
        }

        $tasks = count($projects) ? $taskRepository->findBy(['project' => $projects], ['idTask' => 'DESC']) : [];
        $search = trim((string) $request->query->get('search', ''));
        if ($search !== '') {
            $tasks = array_values(array_filter($tasks, static fn (Task $item): bool => str_contains(strtolower($item->getTitle() ?? ''), strtolower($search))));
        }

        $projectFilter = $request->query->get('project');
        if ($projectFilter !== null && $projectFilter !== '') {
            $projectId = (int) $projectFilter;
            $tasks = array_values(array_filter($tasks, static fn (Task $item): bool => $item->getProject()?->getIdProject() === $projectId));
        }

        return $this->render('frontOffice/freelancer/Tasks/task.html.twig', [
            'tasks' => $tasks,
            'form_projects' => $projects,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/new', name: 'freelancer_task_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->redirectToRoute('freelancer_task_index');
    }

    #[Route('/{id}/edit', name: 'freelancer_task_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        ApplicationRepository $applicationRepository,
        FreelancerRepository $freelancerRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $freelancer = $freelancerRepository->findByUserId($userId);
        if (!$freelancer) {
            return $this->redirectToRoute('freelancer_dashboard');
        }

        $assignedProjects = $projectRepository->findByFreelancer($freelancer->getIdFreelancer());
        $acceptedProjectIds = $applicationRepository->findAcceptedProjectIdsForFreelancer($freelancer->getIdFreelancer());
        $acceptedProjects = $projectRepository->findByIds($acceptedProjectIds);
        $projectMap = [];
        foreach (array_merge($assignedProjects, $acceptedProjects) as $project) {
            $projectMap[$project->getIdProject()] = $project;
        }
        $projects = array_values($projectMap);
        $task = $taskRepository->find($id);
        $allowedProjectIds = array_map(static fn ($p) => $p->getIdProject(), $projects);
        if (!$task || !in_array($task->getProject()?->getIdProject(), $allowedProjectIds, true)) {
            throw $this->createNotFoundException('Task not found.');
        }

        $form = $this->createForm(TaskType::class, $task, ['projects' => $projects]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Task updated successfully.');

            return $this->redirectToRoute('freelancer_task_index', [], Response::HTTP_SEE_OTHER);
        }

        $tasks = count($projects) ? $taskRepository->findBy(['project' => $projects], ['idTask' => 'DESC']) : [];

        return $this->render('frontOffice/freelancer/Tasks/task.html.twig', [
            'tasks' => $tasks,
            'form_projects' => $projects,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}', name: 'freelancer_task_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        ApplicationRepository $applicationRepository,
        FreelancerRepository $freelancerRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $freelancer = $freelancerRepository->findByUserId($userId);
        if (!$freelancer) {
            return $this->redirectToRoute('freelancer_dashboard');
        }

        $assignedProjects = $projectRepository->findByFreelancer($freelancer->getIdFreelancer());
        $acceptedProjectIds = $applicationRepository->findAcceptedProjectIdsForFreelancer($freelancer->getIdFreelancer());
        $acceptedProjects = $projectRepository->findByIds($acceptedProjectIds);
        $projectMap = [];
        foreach (array_merge($assignedProjects, $acceptedProjects) as $project) {
            $projectMap[$project->getIdProject()] = $project;
        }
        $projects = array_values($projectMap);
        $allowedProjectIds = array_map(static fn ($p) => $p->getIdProject(), $projects);
        $task = $taskRepository->find($id);
        if (!$task || !in_array($task->getProject()?->getIdProject(), $allowedProjectIds, true)) {
            throw $this->createNotFoundException('Task not found.');
        }

        if ($this->isCsrfTokenValid('delete'.$task->getIdTask(), (string) $request->request->get('_token'))) {
            $entityManager->remove($task);
            $entityManager->flush();
            $this->addFlash('success', 'Task deleted successfully.');
        }

        return $this->redirectToRoute('freelancer_task_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export/pdf', name: 'freelancer_task_export_pdf', methods: ['GET'])]
    public function exportPdf(): Response
    {
        $this->addFlash('info', 'PDF export will be added soon.');
        return $this->redirectToRoute('freelancer_task_index');
    }
}
