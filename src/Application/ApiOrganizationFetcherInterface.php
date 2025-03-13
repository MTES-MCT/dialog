<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Organization\View\OrganizationFetchedView;

interface ApiOrganizationFetcherInterface
{
    public function findBySiret(string $siret): OrganizationFetchedView;
}
