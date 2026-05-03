<?php

namespace App\Tests\Service\users\user;

use App\Entity\users\user\User;
use App\Service\users\user\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testValidUser()
    {
        $user = new User();
        $user->setName('Alice Smith');
        $user->setPassword('SecurePass123'); // Plus de 8 caractères
        
        $manager = new UserManager();
        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithoutName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $user = new User();
        $user->setPassword('SecurePass123'); // Nom vide
        
        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithShortPassword()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 8 caractères.');

        $user = new User();
        $user->setName('Bob Jones');
        $user->setPassword('1234'); // Trop court
        
        $manager = new UserManager();
        $manager->validate($user);
    }
}
