<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Connection\Adapter\Pdo;

use Sokil\Mysql\PartitionManager\Connection\Exception\ConnectionException;
use Sokil\Mysql\PartitionManager\Connection\ConnectionInterface;

class PdoConnection implements ConnectionInterface
{
    public function __construct(
        private readonly \PDO $pdo
    ) {
        if ($this->pdo->getAttribute(\PDO::ATTR_ERRMODE) !== \PDO::ERRMODE_EXCEPTION) {
            throw new \RuntimeException('PDO must throw exceptions on error');
        }

        if ($this->pdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE) === \PDO::FETCH_ASSOC) {
            throw new \RuntimeException('PDO must fetch associative arrays');
        }
    }

    /**
     * @throws ConnectionException
     */
    public function execute(string $sql, array $params = []): void
    {
        try {
            $this->pdo->prepare($sql)->execute($params);
        } catch (\Throwable $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws ConnectionException
     */
    public function fetchOne(string $sql, array $params = []): array
    {
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($params);

            $row = $statement->fetch(mode: \PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                throw new ConnectionException('Can not fetch row');
            }

            return $row;
        } catch (\Throwable $e) {
            throw new ConnectionException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @throws ConnectionException
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            $sth = $this->pdo->prepare($sql);
            $sth->execute($params);

            return $sth->fetchAll(mode: \PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            throw new ConnectionException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }
}
