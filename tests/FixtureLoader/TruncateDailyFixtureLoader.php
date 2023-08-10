<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\FixtureLoader;

use Sokil\Mysql\PartitionManager\Connection\ConnectionInterface;

class TruncateDailyFixtureLoader
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
        PARTITION BY RANGE (DAYOFMONTH(`createdAt`))
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
         PARTITION p12 VALUES LESS THAN (13) ENGINE = InnoDB,
         PARTITION p13 VALUES LESS THAN (14) ENGINE = InnoDB,
         PARTITION p14 VALUES LESS THAN (15) ENGINE = InnoDB,
         PARTITION p15 VALUES LESS THAN (16) ENGINE = InnoDB,
         PARTITION p16 VALUES LESS THAN (17) ENGINE = InnoDB,
         PARTITION p17 VALUES LESS THAN (18) ENGINE = InnoDB,
         PARTITION p18 VALUES LESS THAN (19) ENGINE = InnoDB,
         PARTITION p19 VALUES LESS THAN (20) ENGINE = InnoDB,
         PARTITION p20 VALUES LESS THAN (21) ENGINE = InnoDB,
         PARTITION p21 VALUES LESS THAN (22) ENGINE = InnoDB,
         PARTITION p22 VALUES LESS THAN (23) ENGINE = InnoDB,
         PARTITION p23 VALUES LESS THAN (24) ENGINE = InnoDB,
         PARTITION p24 VALUES LESS THAN (25) ENGINE = InnoDB,
         PARTITION p25 VALUES LESS THAN (26) ENGINE = InnoDB,
         PARTITION p26 VALUES LESS THAN (27) ENGINE = InnoDB,
         PARTITION p27 VALUES LESS THAN (28) ENGINE = InnoDB,
         PARTITION p28 VALUES LESS THAN (29) ENGINE = InnoDB,
         PARTITION p29 VALUES LESS THAN (30) ENGINE = InnoDB,
         PARTITION p30 VALUES LESS THAN (31) ENGINE = InnoDB,
         PARTITION p31 VALUES LESS THAN (32) ENGINE = InnoDB)
        ";

        $this->connection->execute($sql);

        for ($i = 1; $i <= 31; $i++) {
            $sql = "INSERT INTO `{$tableName}` VALUES(?, ?)";
            $value = new \DateTimeImmutable();
            $date = $value->setDate(2021, 01, $i)->format('Y-m-d');
            $this->connection->execute($sql, [$i, $date]);
        }
    }
}
