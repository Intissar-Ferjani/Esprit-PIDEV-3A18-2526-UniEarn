<?php

namespace App\Service\users\user;

use App\Entity\users\user\User;

class UserManager
{
    public function validate(User $user): bool
    {
        if (empty($user->getName())) {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }

        $password = $user->getPassword();
        if (empty($password) || strlen($password) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        return true;
    }
}
