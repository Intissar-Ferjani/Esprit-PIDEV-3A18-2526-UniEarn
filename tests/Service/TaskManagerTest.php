<?php

namespace App\Tests\Service;

use App\Entity\task\Task;
use App\Enum\TaskStatus;
use App\Service\TaskManager;
use PHPUnit\Framework\TestCase;

class TaskManagerTest extends TestCase
{
    public function testValidTask()
    {
        $task = new Task();
        $task->setDateAssign(new \DateTime('2026-05-01'));
        $task->setDeadline(new \DateTime('2026-05-10'));
        $task->setTaskStatus(TaskStatus::TODO);

        $manager = new TaskManager();
        $this->assertTrue($manager->validate($task));
    }

    public function testTaskWithInvalidDeadline()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date limite doit être postérieure à la date d\'assignation.');

        $task = new Task();
        $task->setDateAssign(new \DateTime('2026-05-10'));
        $task->setDeadline(new \DateTime('2026-05-01')); // La deadline est avant la date d'assignation

        $manager = new TaskManager();
        $manager->validate($task);
    }

    public function testTaskInReviewWithoutSubmission()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Une tâche soumise doit contenir un lien ou un fichier de soumission.');

        $task = new Task();
        $task->setDateAssign(new \DateTime('2026-05-01'));
        $task->setDeadline(new \DateTime('2026-05-10'));
        $task->setTaskStatus(TaskStatus::REVIEW);
        // Aucun lien ou fichier fourni

        $manager = new TaskManager();
        $manager->validate($task);
    }

    public function testTaskInReviewWithSubmissionLink()
    {
        $task = new Task();
        $task->setDateAssign(new \DateTime('2026-05-01'));
        $task->setDeadline(new \DateTime('2026-05-10'));
        $task->setTaskStatus(TaskStatus::REVIEW);
        $task->setSubmissionLink('https://github.com/mon-projet');

        $manager = new TaskManager();
        $this->assertTrue($manager->validate($task));
    }
}
