<?php
declare(strict_types=1);

namespace App\Application\Dto;

final class ItemRecord
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly int    $availableQuantity,
       # public readonly int    $reservedQuantity
    ) {}
}
