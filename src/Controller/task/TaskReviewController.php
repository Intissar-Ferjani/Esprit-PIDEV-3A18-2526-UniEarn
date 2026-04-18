<?php

namespace App\Controller\task;

use App\Entity\task\Task;
use App\Enum\TaskStatus;
use App\Form\task\TaskDenialType;
use App\Form\task\TaskSubmissionType;
use App\Repository\candidature\ApplicationRepository;
use App\Repository\project\ProjectRepository;
use App\Repository\task\TaskRepository;
use App\Repository\users\client\ClientRepository;
use App\Repository\users\freelancer\FreelancerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class TaskReviewController extends AbstractController
{
    #[Route('/freelancer/tasks/{id}/submit-review', name: 'freelancer_task_submit_review', methods: ['GET', 'POST'])]
    public function submitForReview(
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
            throw $this->createAccessDeniedException('Only freelancers can submit tasks for review.');
        }

        $task = $taskRepository->find($id);
        $assignedProjects = $projectRepository->findByFreelancer($freelancer->getIdFreelancer());
        $acceptedProjectIds = $applicationRepository->findAcceptedProjectIdsForFreelancer($freelancer->getIdFreelancer());
        $acceptedProjects = $projectRepository->findByIds($acceptedProjectIds);
        $allowedProjectIds = array_map(
            static fn ($project): ?int => $project->getIdProject(),
            array_merge($assignedProjects, $acceptedProjects)
        );

        if (!$task || !in_array($task->getProject()?->getIdProject(), $allowedProjectIds, true)) {
            throw $this->createNotFoundException('Task not found.');
        }

        if ($task->getTaskStatus() === TaskStatus::DONE) {
            throw $this->createAccessDeniedException('A completed task cannot be submitted again.');
        }

        if (!$request->isMethod('POST')) {
            return $this->redirectToRoute('freelancer_task_index');
        }

        $form = $this->createForm(TaskSubmissionType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('submissionFile')->getData();
            if ($uploadedFile instanceof UploadedFile) {
                // file logic
                $uploadDirectory = $this->getParameter('kernel.project_dir').'/public/uploads/task_submissions';
                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0775, true);
                }

                $safeFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^A-Za-z0-9_-]/', '-', $safeFilename) ?: 'submission';
                $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin');

                $uploadedFile->move($uploadDirectory, $newFilename);
                $task->setSubmissionFile($newFilename);
            }

            $task->setTaskStatus(TaskStatus::REVIEW);
            $task->setClientFeedback(null);

            $entityManager->flush();
            $this->addFlash('success', 'Review submitted successfully.');

            return $this->redirectToRoute('freelancer_task_index');
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Review submission failed. Please ensure you provide either a link or a file.');
        }

        return $this->redirectToRoute('freelancer_task_index');
    }

    #[Route('/client/tasks/{id}/accept', name: 'client_task_accept_review', methods: ['POST'])]
    public function acceptReview(
        int $id,
        Request $request,
        TaskRepository $taskRepository,
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $client = $clientRepository->findByUserId($userId);
        if (!$client) {
            throw $this->createAccessDeniedException('Only clients can review tasks.');
        }

        $task = $taskRepository->find($id);
        if (!$task || $task->getProject()?->getClient()?->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Task not found.');
        }

        if ($task->getTaskStatus() !== TaskStatus::REVIEW) {
            throw $this->createAccessDeniedException('Only tasks in review can be accepted.');
        }

        if (!$this->isCsrfTokenValid('accept-review'.$task->getIdTask(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $task->setTaskStatus(TaskStatus::DONE);
        $task->setClientFeedback(null);
        $entityManager->flush();

        return $this->redirectToRoute('client_project_index');
    }

    #[Route('/client/tasks/{id}/deny', name: 'client_task_deny_review', methods: ['GET', 'POST'])]
    public function denyReview(
        int $id,
        Request $request,
        TaskRepository $taskRepository,
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('user_login');
        }

        $client = $clientRepository->findByUserId($userId);
        if (!$client) {
            throw $this->createAccessDeniedException('Only clients can review tasks.');
        }

        $task = $taskRepository->find($id);
        if (!$task || $task->getProject()?->getClient()?->getIdClient() !== $client->getIdClient()) {
            throw $this->createNotFoundException('Task not found.');
        }

        if ($task->getTaskStatus() !== TaskStatus::REVIEW) {
            throw $this->createAccessDeniedException('Only tasks in review can be denied.');
        }

        $form = $this->createForm(TaskDenialType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setTaskStatus(TaskStatus::IN_PROGRESS);
            $entityManager->flush();

            return $this->redirectToRoute('client_project_index');
        }

        return $this->render('frontOffice/client/task/deny_review.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }
}
