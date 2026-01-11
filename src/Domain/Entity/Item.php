<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class Item
{
    public function __construct(
        private int $id,
        private string $sku,
        private string $name,
        private int $availableQuantity
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function sku(): string
    {
        return $this->sku;
    }

    public function availableQuantity(): int
    {
        return $this->availableQuantity;
    }

    public function canReserve(int $quantity): bool
    {
        return $quantity > 0 && $this->availableQuantity >= $quantity;
    }

    public function reserve(int $quantity): void
    {
        if (!$this->canReserve($quantity)) {
            throw new \DomainException('Insufficient stock');
        }

        $this->availableQuantity -= $quantity;
    }

    public function release(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $this->availableQuantity += $quantity;
    }
}
