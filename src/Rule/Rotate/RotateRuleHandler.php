<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule\Rotate;

use Sokil\Mysql\PartitionManager\PartitionManager;
use Sokil\Mysql\PartitionManager\Rule\RuleHandlerInterface;
use Sokil\Mysql\PartitionManager\Rule\AbstractRule;
use Sokil\Mysql\PartitionManager\Rule\RuleHandleResult;
use Sokil\Mysql\PartitionManager\ValueObject\Partition;

class RotateRuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private readonly PartitionManager $partitionManager
    ) {
    }

    public function handle(\DateTimeImmutable $now, AbstractRule $rule): RuleHandleResult
    {
        if (!$rule instanceof RotateRule) {
            throw new \InvalidArgumentException('Invalid rule passed');
        }

        $minPartitionsDateTime = $now->modify("-{$rule->remainPartitionsCount} {$rule->range->value} midnight");
        $maxPartitionsDateTime = $now->modify("+{$rule->createPartitionsCount} {$rule->range->value} midnight");

        $existingPartitions = $this->partitionManager->getPartitions($rule->tableName);

        if (count($existingPartitions) === 0) {
            throw new \Exception('Partitions not found for table ' . $rule->tableName);
        }

        $partitionsToDrop = [];
        $lastExistingPartition = null;

        foreach ($existingPartitions as $existingPartition) {
            if ($existingPartition->lessThenTimestamp <= $minPartitionsDateTime->getTimestamp()) {
                $partitionsToDrop[] = $existingPartition;
            }

            if (
                $lastExistingPartition === null ||
                $existingPartition->lessThenTimestamp > $lastExistingPartition->lessThenTimestamp
            ) {
                $lastExistingPartition = $existingPartition;
            }
        }

        $partitionNamePattern = $this->getPartitionNamePattern($lastExistingPartition);

        // partition to add start time
        $partitionToAddTime = \DateTimeImmutable::createFromFormat(
            'U',
            (string)$lastExistingPartition->lessThenTimestamp
        );

        if ($partitionToAddTime < $minPartitionsDateTime) {
            $partitionToAddTime = $minPartitionsDateTime;
        }

        // prepare partitions to add
        $partitionsToAdd = [];
        while ($partitionToAddTime <= $maxPartitionsDateTime) {
            $partitionName = sprintf('p%s', $partitionToAddTime->format($partitionNamePattern));

            $partitionToAddTime = $partitionToAddTime->modify(
                sprintf('+1 %s', $rule->range->value)
            );

            $partitionsToAdd[] = new Partition(
                $partitionName,
                $partitionToAddTime->getTimestamp(),
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
