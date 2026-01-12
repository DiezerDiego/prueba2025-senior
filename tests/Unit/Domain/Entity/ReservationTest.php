<?php
declare(strict_types=1);


use App\Domain\Entity\Reservation;
use App\Domain\Enum\ReservationStatus;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    public function test_pending_reservation_can_be_confirmed(): void
    {
        $reservation = $this->createPendingReservation();

        $reservation->markNeedsConfirmation();

        $this->assertSame(
            ReservationStatus::NEEDS_CONFIRMATION,
            $reservation->status()
        );
    }

    public function test_non_pending_reservation_cannot_be_confirmed(): void
    {
        $reservation = $this->createPendingReservation();
        $this->expectException(DomainException::class);
        $reservation->markConfirmed();
    }

    public function test_pending_reservation_expires_when_time_passed(): void
    {
        $reservation = new Reservation(
            id: 1,
            itemId: 1,
            idempotencyKey: 'key',
            quantity: 2,
            status: ReservationStatus::PENDING,
            expiresAt: new DateTimeImmutable('-1 minute'),
            createdAt: new DateTimeImmutable('-10 minutes'),
            updatedAt: new DateTimeImmutable('-10 minutes')
        );

        $this->assertTrue(
            $reservation->isExpired(new DateTimeImmutable())
        );
    }

    public function test_pending_reservation_can_be_marked_expired(): void
    {
        $reservation = $this->createPendingReservation();

        $reservation->markExpired();

        $this->assertSame(
            ReservationStatus::EXPIRED,
            $reservation->status()
        );
    }

    private function createPendingReservation(): Reservation
    {
        $now = new DateTimeImmutable();

        return new Reservation(
            id: 1,
            itemId: 1,
            idempotencyKey: 'key',
            quantity: 2,
            status: ReservationStatus::PENDING,
            expiresAt: $now->modify('+5 minutes'),
            createdAt: $now,
            updatedAt: $now
        );
    }
}
