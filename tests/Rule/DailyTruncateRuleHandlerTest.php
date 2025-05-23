<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule;

use Psr\Clock\ClockInterface;
use Sokil\Mysql\PartitionManager\AbstractTestCase;
use Sokil\Mysql\PartitionManager\FixtureLoader\TruncateDailyFixtureLoader;
use Sokil\Mysql\PartitionManager\PartitionManager;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRule;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRuleHandler;
use Sokil\Mysql\PartitionManager\RuleRunner;
use Sokil\Mysql\PartitionManager\ValueObject\RunAt;
use Sokil\Mysql\PartitionManager\ValueObject\TruncatePeriod;

class DailyTruncateRuleHandlerTest extends AbstractTestCase
{
    private string $tableName = 'test_table';

    public function setUp(): void
    {
        $connection = $this->getConnectionRegistry()->getConnection('default');
        $fixtureLoader = new TruncateDailyFixtureLoader($connection);

        $this->dropTable('default', $this->tableName);
        $fixtureLoader->load($this->tableName);
    }

    public function tearDown(): void
    {
        $this->dropTable('default', $this->tableName);
    }

    /**
     * @dataProvider rulesDataProvider
     */
    public function testRunHandler(int $currentDay, int $storeCount, array $truncated): void
    {
        $partitionManager = new PartitionManager($this->getConnectionRegistry());

        $runAt = new RunAt('2023-01-' . $currentDay . '00:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable($runAt->value));


        $rule = new TruncateRule(
            'default',
            $this->tableName,
            $runAt,
            $storeCount,
            TruncatePeriod::DayOfMonth,
        );

        $ruleRunner = new RuleRunner(
            [
                TruncateRule::class => new TruncateRuleHandler($partitionManager),
            ],
            $clock,
        );

        $results = $ruleRunner->run([$rule]);

        $this->assertCount(1, $results);

        /** @var RuleHandleResult $result */
        $result = current($results);

        $this->assertEquals($this->tableName, $result->rule->tableName);
        $this->assertEquals(null, $result->addedPartitions);
        $this->assertEquals(null, $result->droppedPartitions);
        $this->assertEquals(count($truncated), $result->truncatedPartitions);

        for ($i = 1; $i <= 31; $i++) {
            $expectedCount = in_array($i, $truncated) ? 0 : 1;
            $partitionName = sprintf('p%02d', $i);
            $sql = "SELECT * FROM `{$this->tableName}` PARTITION ({$partitionName})";

            $connection = $this->getConnectionRegistry()->getConnection($rule->connectionName);
            $rowsFromPartition = $connection->fetchAll($sql);

            $this->assertCount(
                $expectedCount,
                $rowsFromPartition,
                sprintf(
                    'Partition p%s assert failed. Actual value: %s, expected: %s',
                    $partitionName,
                    count($rowsFromPartition),
                    $expectedCount,
                ),
            );
        }
    }

    public static function rulesDataProvider(): array
    {
        return [
            [
                'currentDay' => 10,
                'storeCount' => 21,
                'truncated' => [11, 12, 13, 14, 15, 16, 17, 18, 19],
            ],
            [
                'currentDay' => 20,
                'storeCount' => 7,
                'truncated' => [21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            ],
        ];
    }
}
