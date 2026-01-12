<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mysql;

use App\Domain\Entity\Reservation;
use App\Domain\Repository\ReservationRepository;
use DateTimeImmutable;
use App\Domain\Enum\ReservationStatus as EnumReservationStatus;
use PDO;
use RuntimeException;

final class MysqlReservationRepository implements ReservationRepository
{
    public function __construct(private PDO $pdo) {}

    public function save(Reservation $reservation): void
    {
        if ($reservation->id() === 0) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO reservations 
                (item_id, idempotency_key, quantity, status, expires_at, created_at, updated_at)
                VALUES (:item, :key, :qty, :status, :expires, NOW(), NOW())'
            );

            $stmt->execute([
                'item'   => $reservation->itemId(),
                'key'    => $reservation->idempotencyKey(),
                'qty'    => $reservation->quantity(),
                'status' => $reservation->status()->value,
                'expires'=> $reservation->expiresAt()->format('Y-m-d H:i:s'),
            ]);
            $reservation->setId((int) $this->pdo->lastInsertId());
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE reservations 
             SET status = :status, updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'status' => $reservation->status()->value,
            'id'     => $reservation->id(),
        ]);
    }

    public function getById(int $id): Reservation
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM reservations WHERE id = :id FOR UPDATE'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        if (!$row) {
            throw new \DomainException('Reservation not found');
        }

        return $this->map($row);
    }

    public function findExpiredPendingForUpdate(
        DateTimeImmutable $now,
        int $limit=50
    ): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM reservations
             WHERE status = :status AND expires_at <= :now
             ORDER BY id
             LIMIT :limit
             FOR UPDATE'
        );

        $stmt->bindValue('status', EnumReservationStatus::PENDING->value);
        $stmt->bindValue('now', $now->format('Y-m-d H:i:s'));
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);

        $stmt->execute();

        return array_map(
            fn ($row) => $this->map($row),
            $stmt->fetchAll()
        );
    }

    private function map(array $row): Reservation
    {
        return new Reservation(
            (int)$row['id'],
            (int)$row['item_id'],
            $row['idempotency_key'],
            (int)$row['quantity'],
            EnumReservationStatus::from($row['status']),
            new DateTimeImmutable($row['expires_at']),
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['updated_at']),
        );
    }
}
