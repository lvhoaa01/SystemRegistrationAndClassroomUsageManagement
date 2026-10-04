<?php

declare(strict_types=1);

namespace App\Shared\Database;

use PDO;
use PDOException;

final class TransactionManager
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function run(callable $callback, int $maxRetries = 2): mixed
    {
        $attempt = 0;

        retry:
        ++$attempt;
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this->pdo);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $isDeadlock = $exception instanceof PDOException
                && in_array((string) $exception->getCode(), ['40001', '1213'], true);

            if ($isDeadlock && $attempt <= $maxRetries) {
                usleep(random_int(20_000, 80_000));
                goto retry;
            }

            throw $exception;
        }
    }
}

