<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mysql;

use App\Domain\Repository\IdempotencyRepository;
use App\Application\Dto\IdempotencyRecord;
use PDO;

final class MysqlIdempotencyRepository implements IdempotencyRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByKey(string $key): ?IdempotencyRecord
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM idempotency_requests WHERE idempotency_key = :key'
        );
        $stmt->execute(['key' => $key]);

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new IdempotencyRecord(
            $row['idempotency_key'],
            $row['payload_hash'],
            (int)$row['reservation_id']
        );
    }

    public function save(
        string $key,
        string $payloadHash,
        int $reservationId
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO idempotency_requests
             (idempotency_key, payload_hash, reservation_id,created_at)
             VALUES (:key, :hash, :res,NOW())'
        );

        $stmt->execute([
            'key'  => $key,
            'hash' => $payloadHash,
            'res'  => $reservationId,
        ]);
    }
}
