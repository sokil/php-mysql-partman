<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule\Truncate;

use Sokil\Mysql\PartitionManager\Rule\AbstractRule;
use Sokil\Mysql\PartitionManager\ValueObject\RunAt;
use Sokil\Mysql\PartitionManager\ValueObject\TruncatePeriod;

class TruncateRule extends AbstractRule
{
    public function __construct(
        string $tableName,
        RunAt $runAt,
        public readonly int $remainPartitionsCount,
        public readonly TruncatePeriod $truncatePeriod,
    ) {
        parent::__construct($tableName, $runAt);

        if ($this->remainPartitionsCount <= 0) {
            throw new \InvalidArgumentException('Store count must be positive int');
        }
    }
}
