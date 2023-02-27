<?php

declare(strict_types=1);

namespace App\Tests;

trait TimezoneHelper
{
    protected function setDefaultTimezone(?string $timezone)
    {
        date_default_timezone_set($timezone);
    }
}
