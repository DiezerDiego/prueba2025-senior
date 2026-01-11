#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Worker\ConfirmReservationWorker;
use Psr\Log\LoggerInterface;

$container = require __DIR__ . '/../config/container.php';

/** @var ConfirmReservationWorker $worker */
$worker = $container->get(ConfirmReservationWorker::class);
/** @var LoggerInterface $logger */
$logger = $container->get(LoggerInterface::class);

try {
    $worker->run();
    $logger->info('ConfirmReservationWorker completed successfully');
} catch (\Throwable $e) {
    $logger->error('ConfirmReservationWorker failed', ['message' => $e->getMessage()]);
    exit(1);
}
