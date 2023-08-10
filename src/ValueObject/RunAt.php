<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\ValueObject;

/**
 * Time to run rule in format, compatible with {@see \DateTimeImmutable}
 *
 * @example "first day of this month 9am"
 */
class RunAt
{
    public function __construct(public readonly string $value)
    {
        try {
            new \DateTimeImmutable($this->value);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Invalid value of running time');
        }
    }

    public function isRunRequired(\DateTimeImmutable $time): bool
    {
        return $time->format('Y-m-d H') === (new \DateTimeImmutable($this->value))->format('Y-m-d H');
    }
}
