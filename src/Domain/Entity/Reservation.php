<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\ReservationStatus;
use DateTimeImmutable;

final class Reservation
{
    public function __construct(
        private int $id,
        private int $itemId,
        private string $idempotencyKey,
        private int $quantity,
        private ReservationStatus $status,
        private DateTimeImmutable $expiresAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {}

    public function id(): int
    {
        return $this->id;
    }

    public function itemId(): int
    {
        return $this->itemId;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function status(): ReservationStatus
    {
        return $this->status;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function markConfirmed(): void
    {
        if ($this->status !== ReservationStatus::PENDING) {
            throw new \DomainException('Only pending reservations can be confirmed');
        }

        $this->status = ReservationStatus::CONFIRMED;
        $this->touch();
    }

    public function markCancelled(): void
    {
        if ($this->status !== ReservationStatus::PENDING) {
            return;
        }

        $this->status = ReservationStatus::CANCELLED;
        $this->touch();
    }

    public function markExpired(): void
    {
        if ($this->status !== ReservationStatus::PENDING) {
            return;
        }

        $this->status = ReservationStatus::EXPIRED;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->status === ReservationStatus::PENDING
            && $this->expiresAt <= $now;
    }

}
