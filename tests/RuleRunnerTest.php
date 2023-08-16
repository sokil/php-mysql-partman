<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager;

use Psr\Clock\ClockInterface;
use Sokil\Mysql\PartitionManager\FixtureLoader\RotateFixtureLoader;
use Sokil\Mysql\PartitionManager\Rule\Rotate\RotateRuleHandler;
use Sokil\Mysql\PartitionManager\Rule\Rotate\RotateRule;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRule;
use Sokil\Mysql\PartitionManager\Rule\Truncate\TruncateRuleHandler;
use Sokil\Mysql\PartitionManager\ValueObject\RotateRange;
use Sokil\Mysql\PartitionManager\ValueObject\RunAt;

class RuleRunnerTest extends AbstractTestCase
{
    private const TABLE1_NAME = 'test_table1';
    private const TABLE2_NAME = 'test_table2';

    public function setUp(): void
    {
        $connection = $this->getConnectionRegistry()->getConnection('default');
        $fixtureLoader = new RotateFixtureLoader($connection);

        $this->dropTable('default', self::TABLE1_NAME);
        $fixtureLoader->load(self::TABLE1_NAME);

        $this->dropTable('default', self::TABLE2_NAME);
        $fixtureLoader->load(self::TABLE2_NAME);
    }

    public function tearDown(): void
    {
        $this->dropTable('default', self::TABLE1_NAME);
        $this->dropTable('default', self::TABLE2_NAME);
    }

    /**
     * @dataProvider rulesDataProvider
     */
    public function testRunHandlers(
        \DateTimeImmutable $currentDateTime,
        array $rules,
        array $tableNameToExpectedRemainedPartitionsMap
    ): void {
        $partitionsManipulator = new PartitionManager($this->getConnectionRegistry());

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($currentDateTime);

        $ruleRunner = new RuleRunner(
            [
                TruncateRule::class => new TruncateRuleHandler($partitionsManipulator),
                RotateRule::class => new RotateRuleHandler($partitionsManipulator),
            ],
            $clock,
        );

        $result = $ruleRunner->run($rules);

        $this->assertCount(
            count($tableNameToExpectedRemainedPartitionsMap),
            $result
        );

        foreach ($tableNameToExpectedRemainedPartitionsMap as $tableName => $expectedRemainedPartitions) {
            $partitionsName = [];

            $partitions = $partitionsManipulator->getPartitions('default', $tableName);

            foreach ($partitions as $partition) {
                $partitionsName[] = $partition->name;
            }

            sort($partitionsName);
            sort($expectedRemainedPartitions);

            $this->assertEquals($expectedRemainedPartitions, $partitionsName);
        }
    }

    public static function rulesDataProvider(): array
    {
        return [
            'dropOldPrevRemainExistedNext' => [
                'currentDateTime' => new \DateTimeImmutable('2023-05-01 05:00:00'),
                'rules' => [
                    new RotateRule(
                        'default',
                        self::TABLE1_NAME,
                        new RunAt('2023-05-01 05:00:00'),
                        RotateRange::Months,
                        3,
                        2,
                    ),
                ],
                'expected' => [
                    self::TABLE1_NAME => [
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
                ],
            ],
            'dropOldPrevAddNewNext' => [
                'currentDateTime' => new \DateTimeImmutable('2023-05-01 05:00:00'),
                'rules' => [
                    new RotateRule(
                        'default',
                        self::TABLE2_NAME,
                        new RunAt('2023-05-01 05:00:00'),
                        RotateRange::Months,
                        2,
                        8,
                    ),
                ],
                'expected' => [
                    self::TABLE2_NAME => [
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
                    ],
                ],
            ],
            'handleTwoRules' => [
                'currentDateTime' => new \DateTimeImmutable('2023-05-01 05:00:00'),
                'rules' => [
                    new RotateRule(
                        'default',
                        self::TABLE1_NAME,
                        new RunAt('2023-05-01 05:00:00'),
                        RotateRange::Months,
                        3,
                        2,
                    ),
                    new RotateRule(
                        'default',
                        self::TABLE2_NAME,
                        new RunAt('2023-05-01 05:00:00'),
                        RotateRange::Months,
                        3,
                        2,
                    ),
                ],
                'expected' => [
                    self::TABLE1_NAME => [
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
                    self::TABLE2_NAME => [
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
                ],
            ],
            'skipNotPlannedToHandle' => [
                'currentDateTime' => new \DateTimeImmutable('2023-05-01 05:00:00'),
                'rules' => [
                    new RotateRule(
                        'default',
                        self::TABLE2_NAME,
                        new RunAt('2024-05-01 06:00:00'),
                        RotateRange::Months,
                        2,
                        8,
                    ),
                ],
                'expected' => [],
            ],
            'handleTwoRulesWhenOneNotPlannedToHandle' => [
                'currentDateTime' => new \DateTimeImmutable('2023-05-01 05:00:00'),
                'rules' => [
                    new RotateRule(
                        'default',
                        self::TABLE1_NAME,
                        new RunAt('2023-05-01 05:00:00'),
                        RotateRange::Months,
                        3,
                        2,
                    ),
                    new RotateRule(
                        'default',
                        self::TABLE2_NAME,
                        new RunAt('2024-05-01 06:00:00'),
                        RotateRange::Months,
                        2,
                        8,
                    ),
                ],
                'expected' => [
                    self::TABLE1_NAME => [
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
                ],
            ],
        ];
    }
}
