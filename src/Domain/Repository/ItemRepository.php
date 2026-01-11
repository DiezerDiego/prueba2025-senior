<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Item;

interface ItemRepository
{
    public function getBySkuForUpdate(string $sku): Item;

    public function getByIdForUpdate(int $id): Item;

    public function save(Item $item): void;
}
