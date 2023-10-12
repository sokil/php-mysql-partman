# MySQL Partition Manager

## Installation

Add dependency:

```
composer require "sokil/php-mysql-partman"
```

## Usage

### Rules

There are two types of partition management rules: rotation of partitions and truncate of partitions.

Rotation of partitions made when count of partitions is unlimited, so we need to remove obsolete partitions and prepare new
partitions for future data insertions.

Example of partition definition where rotation is required:

```sql
CREATE TABLE `someTableName` (
    `autoincrementId` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `createdAt` timestamp NOT NULL,
    PRIMARY KEY (`autoincrementId`,`createdAt`)
)
ENGINE=InnoDB 
DEFAULT CHARSET=utf8
PARTITION BY RANGE (UNIX_TIMESTAMP(`createdAt`)) (
    PARTITION p20230901 VALUES LESS THAN (UNIX_TIMESTAMP('2023-10-01')) ENGINE = InnoDB,
    PARTITION p20231001 VALUES LESS THAN (UNIX_TIMESTAMP('2023-11-01')) ENGINE = InnoDB,
    PARTITION p20231101 VALUES LESS THAN (UNIX_TIMESTAMP('2023-12-01')) ENGINE = InnoDB,
    PARTITION p20231201 VALUES LESS THAN (UNIX_TIMESTAMP('2024-01-01')) ENGINE = InnoDB,
    PARTITION p20231201 VALUES LESS THAN (UNIX_TIMESTAMP('2024-02-01')) ENGINE = InnoDB
);
```

Truncation of partitions is required in case where count of partitions is limited (like count of days in month or count of months in year).
In this case we need to truncate partition where new data will be stored.

Example of partition definition where truncate is required:

```sql
CREATE TABLE `someTableName` (
    `autoincrementId` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `createdAt` date NOT NULL,
    PRIMARY KEY (`autoincrementId`,`createdAt`)
) 
ENGINE=InnoDB 
DEFAULT CHARSET=utf8
PARTITION BY RANGE (MONTH(`createdAt`))
(
    PARTITION p01 VALUES LESS THAN (2) ENGINE = InnoDB,
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
    PARTITION p12 VALUES LESS THAN (13) ENGINE = InnoDB
)   
```

Rotate and truncate rules defines:
* table where partition management performed
* scheduled time when management will run in format compatible with [\DateTimeImmutable](https://www.php.net/manual/en/datetime.formats.php)
* details of how many partitions to drop, to remain unchanged and to create

#### Rotation Rule

To rotate partition in table we need to define next rule:

```php
$rule = new RotatePartitionRule(
    'someTableName',
    new RunAt('first day of this month 9am'),
    RotateRange::Months,
    remainPartitionsCount: 2,
    $createPartitionsCount: 3,
);
```

This rule means that partitions are managed in table `someTableName` at every first day of this month at 9am UTC, 
partitioning made by months, and we will drop all old partitions ramaining 2 old partitions, and also we create three 
partitions for future inserts.

#### Truncate Rule

To truncate partition in table we need to define next rule:

```php
$rule = new TruncateRule(
    'someTableName',
    new RunAt('first day of this month 9am'),
    remainPartitionsCount: 4,
    TruncatePeriod::Month,
);
```

This rule means that partitions are managed in table `someTableName` at every first day of this month at 9am UTC, all 
partitions except last four will be truncated.

### Runner

All defined rules are passed to `\Sokil\Mysql\PartitionManager\RuleRunner` and will handle all acceptable by `runAt`.

```php
use Sokil\Mysql\PartitionManager\Connection\Adapter\Pdo\PdoConnection;
use Sokil\Mysql\PartitionManager\PartitionManager;
use Sokil\Mysql\PartitionManager\RuleRunner;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRule;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRuleHandler;
use Sokil\Mysql\PartitionManager\Rule\Rotate\RotateRule;
use Sokil\Mysql\PartitionManager\Rule\Rotate\RotateRuleHandler;

$connection = new PdoConnection(new Pdo());

$partitionManager = new PartitionManager($connection);

$ruleRunner = new RuleRunner(
    [
        TruncateRule::class => new TruncateRuleHandler($partitionManager),
        RotateRule::class => new RotateRuleHandler($partitionManager),
    ],
    $clock,
);

$results = $ruleRunner->run([
    new TruncateRule(...),
    new RotateRule(...),
    new TruncateRule(...),
    new RotateRule(...),
]);
```
