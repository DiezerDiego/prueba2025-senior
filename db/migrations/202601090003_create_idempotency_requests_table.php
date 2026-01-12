<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateIdempotencyRequestsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('idempotency_requests');

        $table
            ->addColumn('idempotency_key', 'string', ['limit' => 64])
            ->addColumn('payload_hash', 'string', ['limit' => 64])
            ->addColumn('created_at', 'datetime')
            ->addColumn('reservation_id', 'integer')
            ->addIndex(['idempotency_key'], ['unique' => true])
            ->create();
    }
}
