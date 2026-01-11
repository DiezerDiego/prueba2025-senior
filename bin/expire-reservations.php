#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Worker\ExpireReservationsWorker;
use Psr\Log\LoggerInterface;

$container = require __DIR__ . '/../config/container.php';

/** @var ExpireReservationsWorker $worker */
$worker = $container->get(ExpireReservationsWorker::class);
/** @var LoggerInterface $logger */
$logger = $container->get(LoggerInterface::class);

try {
    $count = $worker->run();
    $logger->info('ExpireReservationsWorker completed', ['expired_count' => $count]);
} catch (\Throwable $e) {
    $logger->error('ExpireReservationsWorker failed', ['message' => $e->getMessage()]);
    exit(1);
}
