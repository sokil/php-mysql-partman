<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\FixtureLoader;

use Sokil\Mysql\PartitionManager\Connection\ConnectionInterface;

class RotateFixtureLoader
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {
    }

    public function load(string $tableName): void
    {
        $sql = "CREATE TABLE `{$tableName}` (
            `autoincrementId` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `createdAt` timestamp NOT NULL,
            PRIMARY KEY (`autoincrementId`,`createdAt`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        PARTITION BY RANGE (UNIX_TIMESTAMP(`createdAt`))
        (PARTITION p20230101 VALUES LESS THAN (UNIX_TIMESTAMP('2023-02-01')) ENGINE = InnoDB,
         PARTITION p20230201 VALUES LESS THAN (UNIX_TIMESTAMP('2023-03-01')) ENGINE = InnoDB,
         PARTITION p20230301 VALUES LESS THAN (UNIX_TIMESTAMP('2023-04-01')) ENGINE = InnoDB,
         PARTITION p20230401 VALUES LESS THAN (UNIX_TIMESTAMP('2023-05-01')) ENGINE = InnoDB,
         PARTITION p20230501 VALUES LESS THAN (UNIX_TIMESTAMP('2023-06-01')) ENGINE = InnoDB,
         PARTITION p20230601 VALUES LESS THAN (UNIX_TIMESTAMP('2023-07-01')) ENGINE = InnoDB,
         PARTITION p20230701 VALUES LESS THAN (UNIX_TIMESTAMP('2023-08-01')) ENGINE = InnoDB,
         PARTITION p20230801 VALUES LESS THAN (UNIX_TIMESTAMP('2023-09-01')) ENGINE = InnoDB,
         PARTITION p20230901 VALUES LESS THAN (UNIX_TIMESTAMP('2023-10-01')) ENGINE = InnoDB,
         PARTITION p20231001 VALUES LESS THAN (UNIX_TIMESTAMP('2023-11-01')) ENGINE = InnoDB,
         PARTITION p20231101 VALUES LESS THAN (UNIX_TIMESTAMP('2023-12-01')) ENGINE = InnoDB,
         PARTITION p20231201 VALUES LESS THAN (UNIX_TIMESTAMP('2024-01-01')) ENGINE = InnoDB)
        ";

        $this->connection->execute($sql);

        for ($i = 1; $i <= 12; $i++) {
            $sql = "INSERT INTO `{$tableName}` VALUES(?, ?)";
            $value = new \DateTimeImmutable();
            $date = $value->setDate(2023, $i, 10)->format('Y-m-d');
            $this->connection->execute($sql, [$i, $date]);
        }
    }
}
