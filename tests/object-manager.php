<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env.local')) {
    (new Dotenv())->bootEnv(__DIR__ . '/../.env.local');
} else {
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
