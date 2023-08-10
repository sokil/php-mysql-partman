<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\ValueObject;

class Partition
{
    public function __construct(
        public readonly string $name,
        public readonly int $lessThenTimestamp,
    ) {
    }
}
