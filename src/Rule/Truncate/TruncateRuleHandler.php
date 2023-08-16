<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule\Truncate;

use Sokil\Mysql\PartitionManager\PartitionManager;
use Sokil\Mysql\PartitionManager\Rule\RuleHandlerInterface;
use Sokil\Mysql\PartitionManager\Rule\AbstractRule;
use Sokil\Mysql\PartitionManager\Rule\RuleHandleResult;
use Sokil\Mysql\PartitionManager\ValueObject\TruncatePeriod;

class TruncateRuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private readonly PartitionManager $partitionManager
    ) {
    }

    /**
     * @param TruncateRule $rule
     */
    public function handle(\DateTimeImmutable $now, AbstractRule $rule): RuleHandleResult
    {
        $remainPartitionsCount = $rule->remainPartitionsCount;

        if ($rule->truncatePeriod === TruncatePeriod::Month) {
            if ($remainPartitionsCount > 10) {
                throw new \OutOfRangeException('Remained partitions count for monthly partitions must be in range between 1 and 10');
            }
        }

        $partitionsToRemain = [];
        $partitionsToTruncate = [];

        $partitions = $this->partitionManager->getPartitions($rule->connectionName, $rule->tableName);

        $period = match ($rule->truncatePeriod) {
            TruncatePeriod::Month => new \DatePeriod(
                $now->modify(sprintf('-%d months', $remainPartitionsCount)),
                new \DateInterval('P1M'),
                $now
            ),

            TruncatePeriod::DayOfMonth => new \DatePeriod(
                $now->modify(sprintf('-%d days', $remainPartitionsCount)),
                new \DateInterval('P1D'),
                $now
            ),
        };

        $truncatePeriodDateFormat = match ($rule->truncatePeriod) {
            TruncatePeriod::Month => 'n',
            TruncatePeriod::DayOfMonth => 'd',
        };

        /**
         * Incrementing by 1, because Mysql PARTITION_DESCRIPTION value is greater by 1 of current day number.
         * @var \DateTimeImmutable $item
         */
        foreach ($period as $item) {
            $partitionsToRemain[] = intval($item->format($truncatePeriodDateFormat)) + 1;
        }

        $currentPartitionValue = intval($now->format($truncatePeriodDateFormat)) + 1;

        foreach ($partitions as $partition) {
            if (
                !in_array($partition->lessThenTimestamp, $partitionsToRemain)
                && $partition->lessThenTimestamp !== $currentPartitionValue
            ) {
                $partitionsToTruncate[] = $partition;
            }
        }

        $affectedCount = 0;
        foreach ($partitionsToTruncate as $partitionToTruncate) {
            $this->partitionManager->truncate($rule->connectionName, $rule->tableName, $partitionToTruncate);
            $affectedCount++;
        }

        return new RuleHandleResult($rule, null, null, $affectedCount);
    }
}
