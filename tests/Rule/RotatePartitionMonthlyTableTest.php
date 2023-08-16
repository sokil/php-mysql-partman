<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule;

use Psr\Clock\ClockInterface;
use Sokil\Mysql\PartitionManager\AbstractTestCase;
use Sokil\Mysql\PartitionManager\FixtureLoader\RotateFixtureLoader;
use Sokil\Mysql\PartitionManager\Rule\Rotate\RotateRuleHandler;
use Sokil\Mysql\PartitionManager\PartitionManager;
use Sokil\Mysql\PartitionManager\Rule\Rotate\RotateRule;
use Sokil\Mysql\PartitionManager\RuleRunner;
use Sokil\Mysql\PartitionManager\ValueObject\Partition;
use Sokil\Mysql\PartitionManager\ValueObject\RotateRange;
use Sokil\Mysql\PartitionManager\ValueObject\RunAt;

class RotatePartitionMonthlyTableTest extends AbstractTestCase
{
    private string $tableName = 'test_table';

    public function setUp(): void
    {
        $connection = $this->getConnectionRegistry()->getConnection('default');
        $fixtureLoader = new RotateFixtureLoader($connection);

        $fixtureLoader->load($this->tableName);
    }

    public function tearDown(): void
    {
        $this->dropTable('default', $this->tableName);
    }

    /**
     * @dataProvider rulesDataProvider
     */
    public function testRunHandler(
        \DateTimeImmutable $currentDate,
        int $remainPartitionsCount,
        int $createPartitionsCount,
        array $expectedPartitionNames,
        int $expectCreated,
        int $expectDeleted
    ): void {
        $runAt = new RunAt($currentDate->format('Y-m-d H:i:s'));

        $rule = new RotateRule(
            'default',
            $this->tableName,
            $runAt,
            RotateRange::Months,
            $remainPartitionsCount,
            $createPartitionsCount
        );

        $partitionManager = new PartitionManager($this->getConnectionRegistry());

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($currentDate);

        $ruleRunner = new RuleRunner(
            [
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

        $actualPartitionNames = array_map(
            fn (Partition $partition) => $partition->name,
            $partitionManager->getPartitions($rule->connectionName, $rule->tableName)
        );

        sort($actualPartitionNames);
        sort($expectedPartitionNames);

        $this->assertEquals($expectedPartitionNames, $actualPartitionNames);
    }

    public static function rulesDataProvider(): array
    {
        return [
            [
                'currentDate' => new \DateTimeImmutable('2023-05-10 00:00:00'),
                'remainPartitionsCount' => 3,
                'createPartitionsCount' => 2,
                'expectedPartitionNames' => [
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
                'currentDate' => new \DateTimeImmutable('2023-10-10 00:00:00'),
                'remainPartitionsCount' => 3,
                'createPartitionsCount' => 5,
                'expectedPartitionNames' => [
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
                'currentDate' => new \DateTimeImmutable('2023-09-10 00:00:00'),
                'remainPartitionsCount' => 10,
                'createPartitionsCount' => 10,
                'expectedPartitionNames' => [
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
            [
                'currentDate' => new \DateTimeImmutable('2025-05-01 00:00:00'),
                'remainPartitionsCount' => 2,
                'createPartitionsCount' => 2,
                'expectedPartitionNames' => [
                    'p20250301', // remain
                    'p20250401', // remain
                    'p20250501', // keep
                    'p20250601', // create
                    'p20250701', // create
                ],
                'expectCreated' => 5,
                'expectDeleted' => 12,
            ],
        ];
    }
}
