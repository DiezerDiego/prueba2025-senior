<?php

declare(strict_types=1);

namespace App\Application\Dto;

use DateTimeImmutable;

final class OutboxEventRecord
{
    public function __construct(
        public readonly int               $id,
        public readonly string            $type,
        public readonly array             $payload,
        public readonly DateTimeImmutable $createdAt
    )
    {
    }
}
