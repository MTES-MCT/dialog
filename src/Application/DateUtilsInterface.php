<?php

declare(strict_types=1);

namespace App\Application;

interface DateUtilsInterface
{
    public function getTomorrow(): \DateTimeImmutable;

    public function getNow(): \DateTimeImmutable;
}
