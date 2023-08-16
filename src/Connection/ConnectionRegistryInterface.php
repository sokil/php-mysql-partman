<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Connection;

interface ConnectionRegistryInterface
{
    public function getConnection(string $name): ConnectionInterface;
}
