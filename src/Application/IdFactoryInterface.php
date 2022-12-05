<?php

declare(strict_types=1);

namespace App\Application;

interface IdFactoryInterface
{
    public function make(): string;
}
