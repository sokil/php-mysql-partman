<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule\Rotate;

use Sokil\Mysql\PartitionManager\PartitionManager;
use Sokil\Mysql\PartitionManager\Rule\RuleHandlerInterface;
use Sokil\Mysql\PartitionManager\Rule\AbstractRule;
use Sokil\Mysql\PartitionManager\Rule\RuleHandleResult;
use Sokil\Mysql\PartitionManager\ValueObject\Partition;

class RotatePartitionRuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private readonly PartitionManager $partitionManager
    ) {
    }

    public function handle(\DateTimeImmutable $now, AbstractRule $rule): RuleHandleResult
    {
        if (!$rule instanceof RotatePartitionRule) {
            throw new \InvalidArgumentException('Invalid rule passed');
        }

        $minPartitionsDateTime = $now->modify("-{$rule->remainPartitionsCount} {$rule->range->value} midnight");
        $maxPartitionsDateTime = $now->modify("+{$rule->createPartitionsCount} {$rule->range->value} midnight");

        $partitions = $this->partitionManager->getPartitions($rule->tableName);

        if (count($partitions) === 0) {
            throw new \Exception('Partitions not found for table ' . $rule->tableName);
        }

        $partitionsToDrop = [];
        $maxPartition = null;

        foreach ($partitions as $partition) {
            if ($partition->lessThenTimestamp <= $minPartitionsDateTime->getTimestamp()) {
                $partitionsToDrop[] = $partition;
            }

            if ($maxPartition === null || $partition->lessThenTimestamp > $maxPartition->lessThenTimestamp) {
                $maxPartition = $partition;
            }
        }

        $partitionNamePattern = $this->getPartitionNamePattern($maxPartition);
        $maxPartitionValue = $maxPartition->lessThenTimestamp;
        $maxExistingPartitionDateTime = \DateTime::createFromFormat('U', (string)$maxPartitionValue);
        $partitionsToAdd = [];

        while ($maxExistingPartitionDateTime <= $maxPartitionsDateTime) {
            $partitionsToAdd[] = new Partition(
                sprintf('p%s', $maxExistingPartitionDateTime->format($partitionNamePattern)),
                $maxExistingPartitionDateTime->modify(sprintf('+1 %s', $rule->range->value))->getTimestamp(),
            );
        }

        $addedPartitions = $this->partitionManager->addPartitions($rule->tableName, $partitionsToAdd);
        $deletedPartitions = $this->partitionManager->dropPartitions($rule->tableName, $partitionsToDrop);

        return new RuleHandleResult(
            $rule,
            $addedPartitions,
            $deletedPartitions,
            null
        );
    }

    private function getPartitionNamePattern(Partition $partition): string
    {
        $partitionName = $partition->name;

        switch (true) {
            case preg_match('/^p\d{8}$/', $partitionName):
                $pattern = 'Ymd';
                break;
            case preg_match('/^p_\d{4}_\d{2}_\d{2}$/', $partitionName):
                $pattern = '_Y_m_d';
                break;
            default:
                throw new \LogicException(sprintf('Unknown partitions name format: "%s"', $partitionName));
        }

        return $pattern;
    }
}
