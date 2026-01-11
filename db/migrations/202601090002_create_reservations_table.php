<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateReservationsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('reservations');

        $table
            ->addColumn('item_id', 'integer')
            ->addColumn('idempotency_key', 'string', ['limit' => 64])
            ->addColumn('quantity', 'integer')
            ->addColumn('status', 'string', ['limit' => 20])
            ->addColumn('expires_at', 'datetime')
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['idempotency_key'], ['unique' => true])
            ->addIndex(['status', 'expires_at'])
            ->create();
    }
}
