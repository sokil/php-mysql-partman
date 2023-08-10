<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule;

class RuleHandleResult
{
    public function __construct(
        public readonly AbstractRule $rule,
        public readonly ?int $addedPartitions,
        public readonly ?int $droppedPartitions,
        public readonly ?int $truncatedPartitions
    ) {
    }
}
