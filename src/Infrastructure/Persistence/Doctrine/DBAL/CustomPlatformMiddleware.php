<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

final class CustomPlatformMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new CustomPlatformDriver($driver);
    }
}
