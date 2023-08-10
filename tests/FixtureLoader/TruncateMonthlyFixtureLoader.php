<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\FixtureLoader;

use Sokil\Mysql\PartitionManager\Connection\ConnectionInterface;

class TruncateMonthlyFixtureLoader
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {
    }

    public function load(string $tableName): void
    {
        $sql = "CREATE TABLE `{$tableName}` (
            `autoincrementId` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `createdAt` date NOT NULL,
            PRIMARY KEY (`autoincrementId`,`createdAt`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        PARTITION BY RANGE (MONTH(`createdAt`))
        (PARTITION p01 VALUES LESS THAN (2) ENGINE = InnoDB,
         PARTITION p02 VALUES LESS THAN (3) ENGINE = InnoDB,
         PARTITION p03 VALUES LESS THAN (4) ENGINE = InnoDB,
         PARTITION p04 VALUES LESS THAN (5) ENGINE = InnoDB,
         PARTITION p05 VALUES LESS THAN (6) ENGINE = InnoDB,
         PARTITION p06 VALUES LESS THAN (7) ENGINE = InnoDB,
         PARTITION p07 VALUES LESS THAN (8) ENGINE = InnoDB,
         PARTITION p08 VALUES LESS THAN (9) ENGINE = InnoDB,
         PARTITION p09 VALUES LESS THAN (10) ENGINE = InnoDB,
         PARTITION p10 VALUES LESS THAN (11) ENGINE = InnoDB,
         PARTITION p11 VALUES LESS THAN (12) ENGINE = InnoDB,
         PARTITION p12 VALUES LESS THAN (13) ENGINE = InnoDB)
        ";

        $this->connection->execute($sql);

        for ($i = 1; $i <= 12; $i++) {
            $sql = "INSERT INTO `{$tableName}` VALUES(?, ?)";
            $value = new \DateTimeImmutable();
            $date = $value->setDate(2021, $i, 10)->format('Y-m-d');
            $this->connection->execute($sql, [$i, $date]);
        }
    }
}
