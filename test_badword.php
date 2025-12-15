<?php
// test_badword.php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__.'/.env');

// Boot Symfony Kernel
$kernel = new App\Kernel($_ENV['APP_ENV'], (bool) $_ENV['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get le service
$badWordService = $container->get(App\Service\BadWordDetectorService::class);

// Test
echo "Test 1: ";
$result = $badWordService->censorBadWords("Ce service est de la merde");
echo $result . "\n\n";

echo "Test 2: ";
$result = $badWordService->censorBadWords("Vous êtes des connards");
echo $result . "\n\n";

echo "Test 3: ";
$result = $badWordService->censorBadWords("Service excellent");
echo $result . "\n\n";

echo "✅ Tests terminés!\n";