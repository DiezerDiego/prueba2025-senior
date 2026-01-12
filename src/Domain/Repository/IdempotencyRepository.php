<?php

declare(strict_types=1);

namespace App\Domain\Repository;
use App\Application\Dto\IdempotencyRecord;

interface IdempotencyRepository
{
    public function findByKey(string $key): ?IdempotencyRecord;

    public function save(
        string $key,
        string $payloadHash,
        int    $reservationId
    ): void;
}
