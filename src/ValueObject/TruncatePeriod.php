<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\ValueObject;

enum TruncatePeriod: string
{
    case Month = 'month';
    case DayOfMonth = 'dayOfMonth';
}
