<?php

namespace App\Enum;

enum TaskStatus: string
{
    case REVIEW = 'Review';
    case DONE = 'Done';
    case TODO = 'TODO';
    case IN_PROGRESS = 'InProgress';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::REVIEW => 'Review',
            self::DONE => 'Done',
            self::TODO => 'To Do',
            self::IN_PROGRESS => 'In Progress',
        };
    }
}
