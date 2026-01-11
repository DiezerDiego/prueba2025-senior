<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mysql;

use App\Domain\Repository\OutboxRepository;
use App\Application\Dto\OutboxEventRecord;
use PDO;

final class MysqlOutboxRepository implements OutboxRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function save(OutboxEventRecord $event): void
    {
        $stmt = $this->connection->prepare("
            INSERT INTO outbox (type, payload, created_at)
            VALUES (:type, :payload, NOW())
        ");
        $stmt->execute([
            'type' => $event->type,
            'payload' => json_encode($event->payload),
        ]);
    }

    public function fetchPending(int $limit = 50): array
    {
        $stmt = $this->connection->prepare("
            SELECT id, type, payload, created_at
            FROM outbox
            WHERE processed_at IS NULL
            ORDER BY created_at ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $events = [];
        foreach ($rows as $row) {
            $events[] = new OutboxEventRecord(
                id: (int)$row['id'],
                type: $row['type'],
                payload: json_decode($row['payload'], true),
                createdAt: new \DateTimeImmutable($row['created_at'])
            );
        }
        return $events;
    }

    public function markProcessed(int $id): void
    {
        $stmt = $this->connection->prepare("
            UPDATE outbox
            SET processed_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
    }
}
