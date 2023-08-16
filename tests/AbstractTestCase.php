<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager;

use Doctrine\DBAL\DriverManager;
use Sokil\Mysql\PartitionManager\Connection\Adapter\DoctrineDbal\DoctrineDbalConnection;;
use Sokil\Mysql\PartitionManager\Connection\Adapter\Pdo\PdoConnection;
use Sokil\Mysql\PartitionManager\Connection\ConnectionRegistry;
use Sokil\Mysql\PartitionManager\Connection\ConnectionInterface;
use PHPUnit\Framework\TestCase;
use Sokil\Mysql\PartitionManager\Connection\ConnectionRegistryInterface;

abstract class AbstractTestCase extends TestCase
{
    public const DRIVER_PDO = 'pdo';
    public const DRIVER_DBAL = 'dbal';

    /**
     * @var array<string, ConnectionInterface|null>
     */
    private static array $connectionRegistry = [
        self::DRIVER_PDO => null,
        self::DRIVER_DBAL => null,
    ];

    protected function getConnectionRegistry(string $driver = self::DRIVER_PDO): ConnectionRegistryInterface
    {
        if (self::$connectionRegistry[$driver] === null) {
            if ($driver === self::DRIVER_PDO) {
                $connection = new PdoConnection(
                    new \PDO(
                        'mysql:host=mysql;dbname=test;charset=utf8mb4',
                        'test',
                        'test'
                    )
                );

                self::$connectionRegistry[$driver] = new ConnectionRegistry([
                    'default' => $connection,
                ]);
            } elseif ($driver === self::DRIVER_DBAL) {
                $connection = new DoctrineDbalConnection(
                    DriverManager::getConnection([
                        'driver' => 'pdo_mysql',
                        'host' => 'mysql',
                        'user' => 'test',
                        'password' => 'test',
                        'dbname' => 'test',
                    ])
                );

                self::$connectionRegistry[$driver] = new ConnectionRegistry([
                    'default' => $connection,
                ]);
            } else {
                throw new \InvalidArgumentException('Unknown driver');
            }
        }

        return self::$connectionRegistry[$driver];
    }

    public function dropTable(string $connectionName, string $tableName): void
    {
        $this->getConnectionRegistry()->getConnection($connectionName)->execute("DROP TABLE IF EXISTS `{$tableName}`");
    }
}
