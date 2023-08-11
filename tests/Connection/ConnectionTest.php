<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Connection;

use Sokil\Mysql\PartitionManager\AbstractTestCase;
use Sokil\Mysql\PartitionManager\Connection\Exception\ConnectionException;

class ConnectionTest extends AbstractTestCase
{
    public static function driverDataProvider()
    {
        return [
            [self::DRIVER_PDO],
            [self::DRIVER_DBAL],
        ];
    }

    /**
     * @dataProvider driverDataProvider
     */
    public function testExecuteError(string $driver)
    {
        $connection = $this->getConnection($driver);

        $this->expectException(ConnectionException::class);
        $connection->execute('drop table ' . uniqid());
    }

    /**
     * @dataProvider driverDataProvider
     */
    public function testFetchOne(string $driver)
    {
        $connection = $this->getConnection($driver);
        $tableName = 'connection_test_' . uniqid();

        try {
            $connection->execute(sprintf('CREATE TABLE %s (id int, value varchar(255)) Engine=InnoDb', $tableName));
            $connection->execute(sprintf('INSERT INTO %s (id, value) VALUES (42, \'some\')', $tableName));
            $row = $connection->fetchOne(
                sprintf('SELECT * FROM %s WHERE id = :id', $tableName),
                [
                    'id' => 42
                ]
            );
        } finally {
            $connection->execute(sprintf('DROP TABLE %s', $tableName));
        }

        $this->assertSame(['id' => 42, 'value' => 'some'], $row);
    }

    /**
     * @dataProvider driverDataProvider
     */
    public function testFetchAll(string $driver)
    {
        $connection = $this->getConnection($driver);
        $tableName = 'connection_test_' . uniqid();

        try {
            $connection->execute(sprintf('CREATE TABLE %s (id int, value varchar(255)) Engine=InnoDb', $tableName));
            $connection->execute(sprintf('
                INSERT INTO %s (id, value) 
                VALUES (42, \'some\'), (43, \'other\'), (44, \'some\')
            ', $tableName));
            $rows = $connection->fetchAll(
                sprintf('SELECT * FROM %s WHERE value = :value', $tableName),
                [
                    'value' => 'some'
                ]
            );
        } finally {
            $connection->execute(sprintf('DROP TABLE %s', $tableName));
        }

        $this->assertSame(
            [
                ['id' => 42, 'value' => 'some'],
                ['id' => 44, 'value' => 'some'],
            ],
            $rows,
        );
    }
}
