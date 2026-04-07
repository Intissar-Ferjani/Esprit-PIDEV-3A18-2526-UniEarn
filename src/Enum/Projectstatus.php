<?php

namespace App\Enum;

enum Projectstatus: string
{
    case REVIEW = 'Review';
    case TODO = 'TODO';
    case DONE = 'Done';
    case IN_PROGRESS = 'InProgress';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::REVIEW => 'Review',
            self::TODO => 'To Do',
            self::DONE => 'Done',
            self::IN_PROGRESS => 'In Progress',
        };
    }
}