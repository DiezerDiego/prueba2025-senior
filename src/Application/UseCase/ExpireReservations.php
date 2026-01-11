<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ReservationRepository;
use App\Domain\Repository\ItemRepository;
use App\Infrastructure\Persistence\TransactionManager;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
final class ExpireReservations
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private ReservationRepository $reservationRepository,
        private ItemRepository $itemRepository,
        private TransactionManager $transactionManager,
        private LoggerInterface $logger
    ) {}

    public function execute(): int
    {
        $now = new DateTimeImmutable();
        $expiredCount = 0;

        while (true) {
            $processed = $this->transactionManager->transactional(
                fn () => $this->expireBatch($now)
            );

            $expiredCount += $processed;

            if ($processed < self::BATCH_SIZE) {
                break;
            }
        }
        $this->logger->info('ExpireReservations completed', ['expired_count' => $expiredCount]);

        return $expiredCount;
    }

    private function expireBatch(DateTimeImmutable $now): int
    {
        $reservations = $this->reservationRepository
            ->findExpiredPendingForUpdate($now, self::BATCH_SIZE);

        foreach ($reservations as $reservation) {
            $this->logger->info('Expiring reservation', [
                'reservation_id' => $reservation->id(),
                'sku' => $reservation->itemId(),
                'quantity' => $reservation->quantity()
            ]);
            if (!$reservation->isExpired($now)) {
                continue;
            }

            $reservation->markExpired();

            $item = $this->itemRepository
                ->getByIdForUpdate($reservation->itemId());

            $item->release($reservation->quantity());

            $this->itemRepository->save($item);
            $this->reservationRepository->save($reservation);
            $this->logger->info('Reservation expired successfully', [
                'reservation_id' => $reservation->id(),
                'available_quantity' => $item->availableQuantity()
            ]);
        }

        return count($reservations);
    }
}
