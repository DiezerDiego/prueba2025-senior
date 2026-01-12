<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use Psr\Log\LoggerInterface;
use App\Domain\Repository\ReservationRepository;
use App\Domain\Repository\ItemRepository;
use App\Infrastructure\Persistence\TransactionManager;
use DateTimeImmutable;
use DomainException;
use App\Domain\Repository\OutboxRepository;
use App\Application\Dto\OutboxEventRecord;
use App\Domain\Enum\ReservationStatus as EnumReservationStatus;

final class ConfirmReservation
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private ItemRepository $itemRepository,
        private OutboxRepository $outboxRepository,
        private TransactionManager $transactionManager,
        private LoggerInterface $logger
    ) {}

    public function execute(int $reservationId): string
    {
        $now = new DateTimeImmutable();

        $result=$this->transactionManager->transactional(function () use ($reservationId, $now) {
            $reservation = $this->reservationRepository
                ->getById($reservationId);
            $this->logger->info('Processing reservation', ['reservation_id' => $reservationId, 'status' => $reservation->status()->value]);

            if ($reservation->status()==EnumReservationStatus::NEEDS_CONFIRMATION) {
                $this->logger->info('Reservation already needs_confirmation', ['reservation_id' => $reservationId]);
                return $reservation->status()->value;
            }
            if ($reservation->status()==EnumReservationStatus::CONFIRMED) {
                $this->logger->info('Reservation already confirmed', ['reservation_id' => $reservationId]);
                return $reservation->status()->value;
            }

            if ($reservation->isExpired($now)) {
                $reservation->markExpired();

                $item = $this->itemRepository
                    ->getByIdForUpdate($reservation->itemId());

                $item->release($reservation->quantity());

                $this->itemRepository->save($item);
                $this->reservationRepository->save($reservation);
                $this->logger->warning('Reservation expired', ['reservation_id' => $reservationId]);

                return $reservation->status()->value;
            }

            $reservation->markNeedsConfirmation();

            $this->reservationRepository->save($reservation);
            $this->logger->info('Reservation Needs confirmed', ['reservation_id' => $reservationId]);

            $outboxEvent = new OutboxEventRecord(
                id: 0,
                type: 'reservation_confirmation',
                payload: [
                    'reservation_id' => $reservation->id(),
                    'quantity'       => $reservation->quantity()
                ],
                createdAt: new DateTimeImmutable()
            );

            $this->outboxRepository->save($outboxEvent);
            return $reservation->status()->value;
        });
        return $result;
    }
}
