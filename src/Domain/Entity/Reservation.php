<?php

declare(strict_types=1);

namespace App\Domain\Entity;


use DateTimeImmutable;
use Domain\Enum\ReservationStatus as EnumReservationStatus;

final class Reservation
{
    public function __construct(
        private int $id,
        private int $itemId,
        private string $idempotencyKey,
        private int $quantity,
        private EnumReservationStatus $status,
        private DateTimeImmutable $expiresAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {}

    public static function createPending(
        int $itemId,
        string $idempotencyKey,
        int $quantity,
        DateTimeImmutable $expiresAt
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: 0,
            itemId: $itemId,
            idempotencyKey: $idempotencyKey,
            quantity: $quantity,
            status: EnumReservationStatus::PENDING,
            expiresAt: $expiresAt,
            createdAt: $now,
            updatedAt: $now
        );
    }

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

    public function status(): EnumReservationStatus
    {
        return $this->status;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function markConfirmed(): void
    {
        if ($this->status !== EnumReservationStatus::PENDING) {
            throw new \DomainException('Only pending reservations can be confirmed');
        }

        $this->status = EnumReservationStatus::CONFIRMED;
        $this->touch();
    }

    public function markCancelled(): void
    {
        if ($this->status !== EnumReservationStatus::PENDING) {
            return;
        }

        $this->status = EnumReservationStatus::CANCELLED;
        $this->touch();
    }

    public function markExpired(): void
    {
        if ($this->status !== EnumReservationStatus::PENDING) {
            return;
        }

        $this->status = EnumReservationStatus::EXPIRED;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->status === EnumReservationStatus::PENDING
            && $this->expiresAt <= $now;
    }

}
