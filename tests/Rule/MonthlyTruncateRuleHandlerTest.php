<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule;

use Psr\Clock\ClockInterface;
use Sokil\Mysql\PartitionManager\AbstractTestCase;
use Sokil\Mysql\PartitionManager\FixtureLoader\TruncateMonthlyFixtureLoader;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRuleHandler;
use Sokil\Mysql\PartitionManager\PartitionManager;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRule;
use Sokil\Mysql\PartitionManager\RuleRunner;
use Sokil\Mysql\PartitionManager\ValueObject\RunAt;
use Sokil\Mysql\PartitionManager\ValueObject\TruncatePeriod;

class MonthlyTruncateRuleHandlerTest extends AbstractTestCase
{
    private string $tableName = 'test_table';

    public function setUp(): void
    {
        $connection = $this->getConnectionRegistry()->getConnection('default');
        $fixtureLoader = new TruncateMonthlyFixtureLoader($connection);
        $fixtureLoader->load($this->tableName);
    }

    public function tearDown(): void
    {
        $this->dropTable('default', $this->tableName);
    }

    /**
     * @dataProvider rulesDataProvider
     */
    public function testRunHandler(int $currentMonth, int $storeCount, array $truncated): void
    {
        $runAt = new RunAt('2023-' . $currentMonth . '-10 00:00:00');

        $rule = new TruncateRule(
            'default',
            $this->tableName,
            $runAt,
            $storeCount,
            TruncatePeriod::Month,
        );

        $partitionManager = new PartitionManager($this->getConnectionRegistry());

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable($runAt->value));

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

        for ($i = 1; $i <= 12; $i++) {
            $expectedRowsCountFromPartition = in_array($i, $truncated) ? 0 : 1;

            $partitionName = sprintf('p%02d', $i);
            $sql = "SELECT count(1) c FROM `{$rule->tableName}` PARTITION ({$partitionName})";

            $connection = $this->getConnectionRegistry()->getConnection($rule->connectionName);
            $actualRowsCountFromPartition = $connection->fetchOne($sql)['c'];

            $this->assertTrue(
                $expectedRowsCountFromPartition === $actualRowsCountFromPartition,
                'Partition %s contains unexpected count of rows',
            );
        }
    }

    public static function rulesDataProvider(): array
    {
        return [
            [
                'currentMonth' => 5,
                'storeCount' => 3,
                'truncated' => [1, 6, 7, 8, 9, 10, 11, 12],
            ],
            [
                'currentMonth' => 5,
                'storeCount' => 5,
                'truncated' => [6, 7, 8, 9, 10, 11],
            ],
            [
                'currentMonth' => 5,
                'storeCount' => 10,
                'truncated' => [6],
            ],
        ];
    }
}
