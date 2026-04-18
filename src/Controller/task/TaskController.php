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

        $projectFilter = trim((string) $request->query->get('project', ''));
        $selectedProjectId = $projectFilter !== '' ? (int) $projectFilter : null;
        if ($selectedProjectId !== null) {
            $tasks = array_values(array_filter($tasks, static fn (Task $item): bool => $item->getProject()?->getIdProject() === $selectedProjectId));
        }

        $boardView = $request->query->get('view') === 'project' ? 'project' : 'status';

        return $this->render('frontOffice/freelancer/Tasks/task.html.twig', [
            'tasks' => $tasks,
            'form_projects' => $projects,
            'project_progress' => $taskRepository->getProgressByProjects($projects),
            'project_forecast' => $taskRepository->getForecastByProjects($projects),
            'selected_project_id' => $selectedProjectId,
            'board_view' => $boardView,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/ai-suggest', name: 'freelancer_task_ai_suggest', methods: ['POST'])]
    public function aiSuggest(
        Request $request,
        ProjectRepository $projectRepository,
        \App\Service\AISuggestionService $aiService,
        EntityManagerInterface $entityManager,
        FreelancerRepository $freelancerRepository,
        ApplicationRepository $applicationRepository
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Not authenticated'], 403);
        }

        $freelancer = $freelancerRepository->findByUserId($userId);
        if (!$freelancer) {
            return $this->json(['error' => 'Not a freelancer'], 403);
        }

        $projectId = $request->request->get('projectId');
        if (!$projectId) {
            $this->addFlash('error', 'Please select a project first.');
            return $this->redirectToRoute('freelancer_task_index');
        }

        $project = $projectRepository->find($projectId);
        
        $assignedProjects = $projectRepository->findByFreelancer($freelancer->getIdFreelancer());
        $acceptedProjectIds = $applicationRepository->findAcceptedProjectIdsForFreelancer($freelancer->getIdFreelancer());
        $acceptedProjects = $projectRepository->findByIds($acceptedProjectIds);
        $allowedProjectIds = array_map(static fn ($p) => $p->getIdProject(), array_merge($assignedProjects, $acceptedProjects));
        
        if (!$project || !in_array($project->getIdProject(), $allowedProjectIds, true)) {
            $this->addFlash('error', 'Invalid project selected.');
            return $this->redirectToRoute('freelancer_task_index');
        }

        $suggestions = $aiService->suggestTasks($project->getTitle() ?? 'Untitled Project', $project->getDescription() ?? '');

        if (empty($suggestions)) {
            $this->addFlash('error', 'AI could not generate suggestions at this time. Please try again.');
        } else {
            $count = 0;
            foreach ($suggestions as $s) {
                if (!isset($s['title'], $s['description'], $s['priority'], $s['deadlineDaysFromNow'])) continue;

                $task = new Task();
                $task->setTitle(mb_substr($s['title'], 0, 255));
                $task->setDescription(mb_substr($s['description'], 0, 255));
                $task->setPriority(in_array($s['priority'], ['High', 'Medium', 'Low']) ? $s['priority'] : 'Medium');
                
                $days = (int) $s['deadlineDaysFromNow'];
                $deadline = new \DateTime();
                if ($days > 0) $deadline->modify("+{$days} days");
                else $deadline->modify("+1 day");
                $task->setDeadline($deadline);
                
                $task->setTaskStatus(\App\Enum\TaskStatus::TODO);
                $task->setRole('Freelancer Assigned Task');
                $task->setProject($project);
                $task->setDateAssign(new \DateTime());
                
                $entityManager->persist($task);
                $count++;
            }
            
            if ($count > 0) {
                $entityManager->flush();
                $this->addFlash('success', "AI successfully created $count new tasks for you!");
            } else {
                $this->addFlash('error', 'AI response was invalid or empty.');
            }
        }

        return $this->redirectToRoute('freelancer_task_index');
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
        $search = trim((string) $request->query->get('search', ''));
        if ($search !== '') {
            $tasks = array_values(array_filter($tasks, static fn (Task $item): bool => str_contains(strtolower($item->getTitle() ?? ''), strtolower($search))));
        }

        $projectFilter = trim((string) $request->query->get('project', ''));
        $selectedProjectId = $projectFilter !== '' ? (int) $projectFilter : null;
        if ($selectedProjectId !== null) {
            $tasks = array_values(array_filter($tasks, static fn (Task $item): bool => $item->getProject()?->getIdProject() === $selectedProjectId));
        }

        $boardView = $request->query->get('view') === 'project' ? 'project' : 'status';

        return $this->render('frontOffice/freelancer/Tasks/task.html.twig', [
            'tasks' => $tasks,
            'form_projects' => $projects,
            'project_progress' => $taskRepository->getProgressByProjects($projects),
            'project_forecast' => $taskRepository->getForecastByProjects($projects),
            'selected_project_id' => $selectedProjectId,
            'board_view' => $boardView,
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
