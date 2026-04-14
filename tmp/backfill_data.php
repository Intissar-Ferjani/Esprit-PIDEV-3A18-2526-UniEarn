<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// 1. Backfill Applications
$applications = $entityManager->getRepository(\App\Entity\candidature\Application::class)->findAll();
foreach ($applications as $app) {
    if ($app->getCompatibilityScore() === null) {
        $app->setCompatibilityScore(rand(45, 95));
    }
}

// 2. Backfill Evaluations
$evaluations = $entityManager->getRepository(\App\Entity\candidature\Evaluation::class)->findAll();
foreach ($evaluations as $eval) {
    if ($eval->getSentiment() === null) {
        $sentiments = ['pos', 'neutral']; // Mostly positive for existing demo data
        $eval->setSentiment($sentiments[array_rand($sentiments)]);
        $eval->setSentimentScore(0.8);
    }
}

$entityManager->flush();
echo "Data backfilled successfully!\n";
