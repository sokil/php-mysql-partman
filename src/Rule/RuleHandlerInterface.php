<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\Rule;

interface RuleHandlerInterface
{
    public function handle(\DateTimeImmutable $now, AbstractRule $rule): RuleHandleResult;
}
