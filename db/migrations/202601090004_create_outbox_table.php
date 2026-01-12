<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOutboxTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('outbox');

        $table
            ->addColumn('type', 'string', ['limit' => 50])
            ->addColumn('payload', 'text')
            ->addColumn('processed_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['processed_at'])
            ->create();
    }
}
