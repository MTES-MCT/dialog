<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Application\DateUtilsInterface;

final class DateUtilsMock implements DateUtilsInterface
{
    public function getTomorrow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2023-05-10');
    }
}
