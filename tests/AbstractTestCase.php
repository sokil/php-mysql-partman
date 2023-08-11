<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager;

use Doctrine\DBAL\DriverManager;
use Sokil\Mysql\PartitionManager\Connection\Adapter\Pdo\DoctrineDbalConnection;
use Sokil\Mysql\PartitionManager\Connection\Adapter\Pdo\PdoConnection;
use Sokil\Mysql\PartitionManager\Connection\ConnectionInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    public const DRIVER_PDO = 'pdo';
    public const DRIVER_DBAL = 'dbal';

    /**
     * @var array<string, ConnectionInterface|null>
     */
    private static array $connections = [
        self::DRIVER_PDO => null,
        self::DRIVER_DBAL => null,
    ];

    protected function getConnection(string $driver = self::DRIVER_PDO): ConnectionInterface
    {
        if (self::$connections[$driver] === null) {
            if ($driver === self::DRIVER_PDO) {
                self::$connections[$driver] = new PdoConnection(
                    new \PDO(
                        'mysql:host=mysql;dbname=test;charset=utf8mb4',
                        'test',
                        'test'
                    )
                );
            } elseif ($driver === self::DRIVER_DBAL) {
                self::$connections[$driver] = new DoctrineDbalConnection(
                    DriverManager::getConnection([
                        'driver' => 'pdo_mysql',
                        'host' => 'mysql',
                        'user' => 'test',
                        'password' => 'test',
                        'dbname' => 'test',
                    ])
                );
            } else {
                throw new \InvalidArgumentException('Unknown driver');
            }
        }

        return self::$connections[$driver];
    }

    public function dropTable(string $tableName): void
    {
        $this->getConnection()->execute("DROP TABLE IF EXISTS `{$tableName}`");
    }
}
