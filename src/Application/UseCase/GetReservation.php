<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ReservationRepository;
use App\Application\Dto\ReservationRecord;
use DomainException;
use Psr\Log\LoggerInterface;

final class GetReservation
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private LoggerInterface $logger
    ) {}

    public function execute(int $id): ReservationRecord
    {
        $reservation = $this->reservationRepository->getById($id);

        if (!$reservation) {
            $this->logger->warning('Reservation not found', ['reservation_id' => $id]);
            throw new DomainException("Reservation with ID {$id} not found");
        }

        $this->logger->info('Reservation fetched successfully', ['reservation_id' => $id]);

        return new ReservationRecord(
            id: $reservation->id(),
            itemId: $reservation->itemId(),
            idempotencyKey: $reservation->idempotencyKey(),
            quantity: $reservation->quantity(),
            status: $reservation->status(),
            expiresAt: $reservation->expiresAt(),
            createdAt: $reservation->createdAt(),
            updatedAt: $reservation->updatedAt()
        );
    }
}
