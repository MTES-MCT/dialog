<?php

declare(strict_types=1);

namespace App\Application;

interface QueryBusInterface
{
    public function handle(QueryInterface $query): mixed;
}
