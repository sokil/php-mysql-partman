<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule;

use Sokil\Mysql\PartitionManager\ValueObject\RunAt;

abstract class AbstractRule
{
    public function __construct(
        public readonly string $connectionName,
        public readonly string $tableName,
        public readonly RunAt $runAt
    ) {
        if (empty($this->tableName)) {
            throw new \InvalidArgumentException('Table name can not be empty');
        }
    }
}
