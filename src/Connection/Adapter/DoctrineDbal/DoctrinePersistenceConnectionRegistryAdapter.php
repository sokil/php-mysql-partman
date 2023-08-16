<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Connection\Adapter\DoctrineDbal;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Sokil\Mysql\PartitionManager\Connection\ConnectionInterface;
use Sokil\Mysql\PartitionManager\Connection\ConnectionRegistryInterface;

/**
 * Used as adapter for {@see \Doctrine\Persistence\ConnectionRegistry}
 */
class DoctrinePersistenceConnectionRegistryAdapter implements ConnectionRegistryInterface
{
    public function __construct(
        private readonly ConnectionRegistry $connectionRegistry
    ) {
    }

    public function getConnection(string $name): ConnectionInterface
    {
        /** @var Connection $dbalConnection */
        $dbalConnection = $this->connectionRegistry->getConnection($name);

        return new DoctrineDbalConnection($dbalConnection);
    }

}
