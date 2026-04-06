<?php

namespace App\Enum;

enum ApplicationStatus: string
{
    case PENDING = 'PENDING';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case WITHDRAWN = 'WITHDRAWN';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ACCEPTED => 'Acceptée',
            self::REJECTED => 'Rejetée',
            self::WITHDRAWN => 'Retirée',
        };
    }
}
