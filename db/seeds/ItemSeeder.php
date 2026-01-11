<?php

declare(strict_types=1);
use Phinx\Seed\AbstractSeed;

final class ItemSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'sku' => 'ITEM-001',
                'name' => 'Test Product',
                'available_quantity' => 10,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->table('items')->insert($data)->save();
    }
}
