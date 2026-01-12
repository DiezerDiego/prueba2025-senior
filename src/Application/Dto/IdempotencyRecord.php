<?php
declare(strict_types=1);

namespace App\Application\Dto;

final class IdempotencyRecord
{
    public function __construct(
        public readonly string $key,
        public readonly string $payloadHash,
        public readonly int $reservationId
    )
    {
    }
}
