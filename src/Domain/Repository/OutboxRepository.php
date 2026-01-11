<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Application\Dto\OutboxEventRecord;

interface OutboxRepository
{
    public function save(OutboxEventRecord $event): void;

    /**
     * @return OutboxEventRecord[]
     */
    public function fetchPending(int $limit = 50): array;

    public function markProcessed(int $id): void;
}
