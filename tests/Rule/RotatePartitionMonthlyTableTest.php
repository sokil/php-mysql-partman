<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule;

use Psr\Clock\ClockInterface;
use Sokil\Mysql\PartitionManager\AbstractTestCase;
use Sokil\Mysql\PartitionManager\FixtureLoader\RotateFixtureLoader;
use Sokil\Mysql\PartitionManager\Rule\Rotate\RotateRuleHandler;
use Sokil\Mysql\PartitionManager\PartitionManager;
use Sokil\Mysql\PartitionManager\Rule\Rotate\RotateRule;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRule;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRuleHandler;
use Sokil\Mysql\PartitionManager\RuleRunner;
use Sokil\Mysql\PartitionManager\ValueObject\RotateRange;
use Sokil\Mysql\PartitionManager\ValueObject\RunAt;

class RotatePartitionMonthlyTableTest extends AbstractTestCase
{
    private string $tableName = 'test_table';

    public function setUp(): void
    {
        $connection = $this->getConnection();
        $fixtureLoader = new RotateFixtureLoader($connection);

        $fixtureLoader->load($this->tableName);
    }

    public function tearDown(): void
    {
        $this->dropTable($this->tableName);
    }

    public function testCheckPartitionData(): void
    {
        $connection = $this->getConnection();

        for ($i = 1; $i <= 12; $i++) {
            $partitionName = sprintf('p2023%02d01', $i);
            $sql = "SELECT * FROM `{$this->tableName}` PARTITION ({$partitionName})";

            $rowsFromPartition = $connection->fetchAll($sql);
            $this->assertCount(
                1,
                $rowsFromPartition,
                sprintf('partition %s expect failed', $partitionName),
            );
        }
    }

    /**
     * @depends testCheckPartitionData
     * @dataProvider rulesDataProvider
     */
    public function testRunHandler(
        int $currentMonth,
        int $remainPartitionsCount,
        int $createPartitionsCount,
        array $partitionsLeft,
        int $expectCreated,
        int $expectDeleted
    ): void {
        $connection = $this->getConnection();
        $runAt = new RunAt('2023-' . $currentMonth . '-10 00:00:00');

        $rule = new RotateRule(
            $this->tableName,
            $runAt,
            RotateRange::Months,
            $remainPartitionsCount,
            $createPartitionsCount
        );

        $partitionManager = new PartitionManager($connection);

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable($runAt->value));

        $ruleRunner = new RuleRunner(
            [
                TruncateRule::class => new TruncateRuleHandler($partitionManager),
                RotateRule::class => new RotateRuleHandler($partitionManager),
            ],
            $clock,
        );

        $results = $ruleRunner->run([$rule]);

        $this->assertCount(1, $results);

        /** @var RuleHandleResult $result */
        $result = current($results);

        $this->assertEquals($this->tableName, $result->rule->tableName);
        $this->assertEquals($expectCreated, $result->addedPartitions);
        $this->assertEquals($expectDeleted, $result->droppedPartitions);
        $this->assertEquals(null, $result->truncatedPartitions);

        $partitions = $partitionManager->getPartitions($this->tableName);

        foreach ($partitions as $partition) {
            $partitionsName[] = $partition->name;
        }

        sort($partitionsName);
        sort($partitionsLeft);

        $this->assertEquals($partitionsLeft, $partitionsName);
    }

    public static function rulesDataProvider(): array
    {
        return [
            [
                'currentMonth' => 5,
                'remainPartitionsCount' => 3,
                'createPartitionsCount' => 2,
                'partitionsLeft' => [
                    'p20230201',
                    'p20230301',
                    'p20230401',
                    'p20230501',
                    'p20230601',
                    'p20230701',
                    'p20230801',
                    'p20230901',
                    'p20231001',
                    'p20231101',
                    'p20231201',
                ],
                'expectCreated' => 0,
                'expectDeleted' => 1,
            ],
            [
                'currentMonth' => 10,
                'remainPartitionsCount' => 3,
                'createPartitionsCount' => 5,
                'partitionsLeft' => [
                    'p20230701',
                    'p20230801',
                    'p20230901',
                    'p20231001',
                    'p20231101',
                    'p20231201',
                    'p20240101',
                    'p20240201',
                    'p20240301',
                ],
                'expectCreated' => 3,
                'expectDeleted' => 6,
            ],
            [
                'currentMonth' => 9,
                'remainPartitionsCount' => 10,
                'createPartitionsCount' => 10,
                'partitionsLeft' => [
                    'p20230101',
                    'p20230201',
                    'p20230301',
                    'p20230401',
                    'p20230501',
                    'p20230601',
                    'p20230701',
                    'p20230801',
                    'p20230901',
                    'p20231001',
                    'p20231101',
                    'p20231201',
                    'p20240101',
                    'p20240201',
                    'p20240301',
                    'p20240401',
                    'p20240501',
                    'p20240601',
                    'p20240701',
                ],
                'expectCreated' => 7,
                'expectDeleted' => 0,
            ],
        ];
    }
}
