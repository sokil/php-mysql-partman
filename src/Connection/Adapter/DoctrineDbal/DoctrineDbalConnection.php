<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Connection\Adapter\DoctrineDbal;

use Doctrine\DBAL\Connection;
use Sokil\Mysql\PartitionManager\Connection\Exception\ConnectionException;
use Sokil\Mysql\PartitionManager\Connection\ConnectionInterface;

class DoctrineDbalConnection implements ConnectionInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function execute(string $sql, array $params = []): void
    {
        try {
            $this->connection->executeStatement($sql, $params);
        } catch (\Throwable $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }

    public function fetchOne(string $sql, array $params = []): array
    {
        try {
            return $this->connection->executeQuery($sql, $params)->fetchAssociative();
        } catch (\Throwable $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
        } catch (\Throwable $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }
}
