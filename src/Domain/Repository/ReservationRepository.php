<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Reservation;
use DateTimeImmutable;

interface ReservationRepository
{
    public function save(Reservation $reservation): void;

    public function getById(int $id): Reservation;

    /**
     * @return Reservation[]
     */
    public function findExpiredPendingForUpdate(
        DateTimeImmutable $now,
        int $limit
    ): array;
}
