<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager;

use Sokil\Mysql\PartitionManager\Connection\ConnectionRegistryInterface;
use Sokil\Mysql\PartitionManager\Connection\Exception\ConnectionException;
use Sokil\Mysql\PartitionManager\ValueObject\Partition;

class PartitionManager
{
    public function __construct(
        private readonly ConnectionRegistryInterface $connectionRegistry,
    ) {
    }

    /**
     * @return Partition[]
     * @throws \Exception
     * @throws ConnectionException
     */
    public function getPartitions(string $connectionName, string $tableName): array
    {
        $connection = $this->connectionRegistry->getConnection($connectionName);

        $result = [];
        $selectDbResult = $connection->fetchOne('SELECT database() as db');

        if (!empty($selectDbResult) && !empty($selectDbResult['db'])) {
            $databaseName = (string)$selectDbResult['db'];
        } else {
            throw new \Exception('empty database name');
        }

        $sql = "SELECT PARTITION_NAME, PARTITION_DESCRIPTION, TABLE_ROWS
                FROM information_schema.partitions
                WHERE TABLE_NAME = :tableName AND TABLE_SCHEMA = :tableSchema
                ORDER BY PARTITION_DESCRIPTION";

        $rows = $connection->fetchAll($sql, [
            'tableName' => $tableName,
            'tableSchema' => $databaseName,
        ]);

        if (empty($rows)) {
            throw new \Exception('Table does not have partitions');
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new \Exception('invalid row structure');
            }

            if (!$row['PARTITION_NAME'] || !$row['PARTITION_DESCRIPTION']) {
                throw new \Exception('Table with unsupported partitions');
            }

            if (!is_numeric($row['PARTITION_DESCRIPTION'])) {
                throw new \Exception('Partition description must be numeric');
            }

            $result[] = new Partition(
                (string)$row['PARTITION_NAME'],
                (int)$row['PARTITION_DESCRIPTION'],
            );
        }

        return $result;
    }

    public function truncate(string $connectionName, string $tableName, Partition $partition): void
    {
        $connection = $this->connectionRegistry->getConnection($connectionName);

        $swapTableName = $tableName . "_exchange_partition_tmp";

        $createTableSql = sprintf('CREATE TABLE `%s` LIKE `%s`', $swapTableName, $tableName);
        $connection->execute($createTableSql);

        $alterTableSql = sprintf('ALTER TABLE `%s` REMOVE PARTITIONING', $swapTableName);
        $connection->execute($alterTableSql);

        $exchangeSql = sprintf(
            'ALTER TABLE `%s` EXCHANGE PARTITION %s WITH TABLE `%s`',
            $tableName,
            $partition->name,
            $swapTableName
        );

        $connection->execute($exchangeSql);

        $dropTableSql = sprintf('DROP TABLE `%s`', $swapTableName);
        $connection->execute($dropTableSql);
    }

    /**
     * @param Partition[] $partitions
     * @throws ConnectionException
     */
    public function addPartitions(string $connectionName, string $tableName, array $partitions): int
    {
        $connection = $this->connectionRegistry->getConnection($connectionName);

        $conditions = [];

        foreach ($partitions as $partition) {
            $conditions[] = sprintf(
                "PARTITION %s VALUES LESS THAN (%d)",
                $partition->name,
                $partition->lessThenTimestamp,
            );
        }

        if ($conditions) {
            $sql = sprintf('ALTER TABLE `%s` ADD PARTITION (%s)', $tableName, implode(',', $conditions));
            $connection->execute($sql);
        }

        return count($partitions);
    }

    /**
     * @param Partition[] $partitions
     * @throws ConnectionException
     */
    public function dropPartitions(string $connectionName, string $tableName, array $partitions): int
    {
        $connection = $this->connectionRegistry->getConnection($connectionName);

        foreach ($partitions as $partition) {
            $sql = sprintf('ALTER TABLE `%s` DROP PARTITION %s', $tableName, $partition->name);
            $connection->execute($sql);
        }

        return count($partitions);
    }
}
