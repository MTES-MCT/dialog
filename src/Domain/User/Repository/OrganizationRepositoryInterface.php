<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Application\User\View\OrganizationView;
use App\Domain\User\Organization;

interface OrganizationRepositoryInterface
{
    /** @return OrganizationView[] */
    public function findAll(): array;

    public function findAllEntities(): array;

    public function findOneByUuid(string $uuid): ?Organization;

    public function findOneBySiret(string $siret): ?Organization;

    public function countOrganizations(): int;

    public function add(Organization $organization): void;

    public function flush(): void;

    public function canInterveneOnGeometry(string $uuid, string $geometry): bool;
}
