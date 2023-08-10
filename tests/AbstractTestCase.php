<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager;

use Sokil\Mysql\PartitionManager\Connection\Adapter\Pdo\PdoConnection;
use Sokil\Mysql\PartitionManager\Connection\ConnectionInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    private static ?ConnectionInterface $connection = null;

    protected function getConnection(): ConnectionInterface
    {
        if (self::$connection === null) {
            $pdo = new \PDO('mysql:host=mysql;dbname=test;charset=utf8mb4', 'test', 'test');
            self::$connection = new PdoConnection($pdo);
        }

        return self::$connection;
    }

    public function dropTable(string $tableName): void
    {
        $this->getConnection()->execute("DROP TABLE IF EXISTS `{$tableName}`");
    }
}
