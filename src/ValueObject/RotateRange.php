<?php

declare(strict_types=1);

namespace Sokil\Mysql\PartitionManager\ValueObject;

enum RotateRange: string
{
    case Months = 'months';
    case Weeks = 'weeks';
    case Days = 'days';
}
