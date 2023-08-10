<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule\Rotate;

use Sokil\Mysql\PartitionManager\Rule\AbstractRule;
use Sokil\Mysql\PartitionManager\ValueObject\RotateRange;
use Sokil\Mysql\PartitionManager\ValueObject\RunAt;

class RotatePartitionRule extends AbstractRule
{
    public function __construct(
        string $tableName,
        RunAt $runAt,
        public readonly RotateRange $range,
        public readonly int $remainPartitionsCount,
        public readonly int $createPartitionsCount,
    ) {
        parent::__construct($tableName, $runAt);

        if ($remainPartitionsCount <= 0) {
            throw new \InvalidArgumentException('Remain partitions count must be positive integer');
        }

        if ($createPartitionsCount <= 0) {
            throw new \InvalidArgumentException('Crete partitions count must be positive integer');
        }
    }
}
