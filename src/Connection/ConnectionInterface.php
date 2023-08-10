<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Connection;

use Sokil\Mysql\PartitionManager\Connection\Exception\ConnectionException;

interface ConnectionInterface
{
    /**
     * @throws ConnectionException
     */
    public function execute(string $sql, array $params = []): void;

    /**
     * @throws ConnectionException
     */
    public function fetchOne(string $sql, array $params = []): array;

    /**
     * @throws ConnectionException
     */
    public function fetchAll(string $sql, array $params = []): array;
}
