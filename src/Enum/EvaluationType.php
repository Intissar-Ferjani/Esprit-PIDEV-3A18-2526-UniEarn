<?php

namespace App\Enum;

enum EvaluationType: string
{
    case CLIENT_TO_FREELANCER = 'CLIENT_TO_FREELANCER';
    case FREELANCER_TO_CLIENT = 'FREELANCER_TO_CLIENT';
    case USER_TO_USER = 'USER_TO_USER';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::CLIENT_TO_FREELANCER => 'Client vers Freelancer',
            self::FREELANCER_TO_CLIENT => 'Freelancer vers Client',
            self::USER_TO_USER => 'Utilisateur vers Utilisateur',
        };
    }
}
