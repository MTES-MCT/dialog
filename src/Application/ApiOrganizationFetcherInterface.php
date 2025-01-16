<?php

declare(strict_types=1);

namespace App\Application;

interface ApiOrganizationFetcherInterface
{
    public function findBySiret(string $siret): array;
}
