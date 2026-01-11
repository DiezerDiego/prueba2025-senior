<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;
use Throwable;

final class TransactionManager
{
    private int $level = 0;

    public function __construct(
        private PDO $connection
    ) {}

    public function transactional(callable $callback): mixed
    {
        $isRootTransaction = $this->level === 0;

        try {
            if ($isRootTransaction) {
                $this->connection->beginTransaction();
            }

            $this->level++;

            $result = $callback();

            $this->level--;

            if ($isRootTransaction) {
                $this->connection->commit();
            }

            return $result;
        } catch (Throwable $e) {
            $this->level = 0;

            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $e;
        }
    }
}
