<?php

declare(strict_types=1);

namespace App\Infrastructure\Worker;

use App\Infrastructure\Persistence\TransactionManager;
use App\Infrastructure\Persistence\MysqlReservationRepository;
use App\Infrastructure\Persistence\MysqlItemRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final class ExpireReservationsWorker
{
    public function __construct(
        private MysqlReservationRepository $reservationRepository,
        private MysqlItemRepository $itemRepository,
        private TransactionManager $transactionManager,
        private LoggerInterface $logger
    ) {}

    public function run(): void
    {
        $now = new DateTimeImmutable();

        $pendingReservations = $this->reservationRepository->getPendingExpired($now);

        foreach ($pendingReservations as $reservation) {
            try {
                $this->transactionManager->transactional(function() use ($reservation) {
                    $item = $this->itemRepository->getByIdForUpdate($reservation->itemId());

                    $reservation->markExpired();
                    $item->release($reservation->quantity());

                    $this->reservationRepository->save($reservation);
                    $this->itemRepository->save($item);
                });
            } catch (\Exception $e) {
                $this->logger->error("Failed to expire reservation {$reservation->id()}: {$e->getMessage()}");
            }
        }
    }
}
