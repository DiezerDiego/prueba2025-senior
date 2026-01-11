<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mysql;

use App\Domain\Entity\Item;
use App\Domain\Repository\ItemRepository;
use PDO;
use RuntimeException;

final class MysqlItemRepository implements ItemRepository
{
    public function __construct(private PDO $pdo) {}

    public function getBySkuForUpdate(string $sku): Item
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM items WHERE sku = :sku FOR UPDATE'
        );
        $stmt->execute(['sku' => $sku]);

        $row = $stmt->fetch();

        if (!$row) {
            throw new \DomainException('Item not found');
        }

        return new Item(
            (int)$row['id'],
            $row['sku'],
            $row['name'],
            (int)$row['available_quantity']
        );
    }

    public function getByIdForUpdate(int $id): Item
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM items WHERE id = :id FOR UPDATE'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        if (!$row) {
            throw new \DomainException('Item not found');
        }

        return new Item(
            (int)$row['id'],
            $row['sku'],
            $row['name'],
            (int)$row['available_quantity']
        );
    }

    public function save(Item $item): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE items SET available_quantity = :qty WHERE id = :id'
        );

        $stmt->execute([
            'qty' => $item->availableQuantity(),
            'id'  => $item->id(),
        ]);
    }
}
