<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager;

use Psr\Clock\ClockInterface;
use Sokil\Mysql\PartitionManager\Rule\AbstractRule;
use Sokil\Mysql\PartitionManager\Rule\RuleHandlerInterface;
use Sokil\Mysql\PartitionManager\Rule\RuleHandleResult;

class RuleRunner
{
    /**
     * @param array<class-string, RuleHandlerInterface> $ruleHandlerLocator
     * @throws \InvalidArgumentException
     */
    public function __construct(
        private readonly array $ruleHandlerLocator,
        private readonly ClockInterface $clock,
    ) {
        foreach ($this->ruleHandlerLocator as $ruleHandler) {
            if (!$ruleHandler instanceof RuleHandlerInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'class `%s` must implement interface `%s`',
                        get_class($ruleHandler),
                        RuleHandlerInterface::class
                    )
                );
            }
        }
    }

    /**
     * @param AbstractRule[] $rules
     *
     * @return RuleHandleResult[]
     */
    public function run(array $rules): array
    {
        $now = $this->clock->now();

        $result = [];

        foreach ($rules as $rule) {
            if ($rule->runAt->isRunRequired($now)) {
                $ruleHandler = $this->ruleHandlerLocator[get_class($rule)] ?? null;
                $result[] = $ruleHandler->handle($now, $rule);
            }
        }

        return $result;
    }
}
