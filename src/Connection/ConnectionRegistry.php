<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Connection;

class ConnectionRegistry implements ConnectionRegistryInterface
{
    /**
     * @psalm-param array<string, ConnectionInterface> $connections
     */
    public function __construct(
        private readonly array $connections
    ) {
    }

    public function getConnection(string $name): ConnectionInterface
    {
        if (empty($this->connections[$name])) {
            throw new \Exception(sprintf('Connection with name "%s" not configured', $name));
        }

        return $this->connections[$name];
    }

}
