<?php

namespace App\Service;

use App\Entity\task\Task;
use App\Enum\TaskStatus;
use InvalidArgumentException;

class TaskManager
{
    /**
     * Valide les règles métier d'une tâche.
     */
    public function validate(Task $task): bool
    {
        // Règle métier 1: La date limite ne peut pas être antérieure à la date d'assignation.
        if ($task->getDeadline() < $task->getDateAssign()) {
            throw new InvalidArgumentException('La date limite doit être postérieure à la date d\'assignation.');
        }

        // Règle métier 2: Une tâche terminée ou en révision doit contenir une preuve de travail (lien ou fichier).
        if (in_array($task->getTaskStatus(), [TaskStatus::REVIEW, TaskStatus::DONE], true)) {
            if (empty($task->getSubmissionLink()) && empty($task->getSubmissionFile())) {
                throw new InvalidArgumentException('Une tâche soumise doit contenir un lien ou un fichier de soumission.');
            }
        }

        return true;
    }
}
