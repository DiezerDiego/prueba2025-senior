<?php
declare(strict_types=1);

namespace App\Application\Dto;

use DateTimeImmutable;
use App\Domain\Enum\ReservationStatus as EnumReservationStatus;

final class ReservationRecord
{
    public function __construct(
        public readonly int $id,
        public readonly int $itemId,
        public readonly string $idempotencyKey,
        public readonly int $quantity,
        public readonly EnumReservationStatus $status,
        public readonly DateTimeImmutable $expiresAt,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt
    ) {}
}
