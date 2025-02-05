<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Invitation;
use App\Domain\User\Organization;

interface InvitationRepositoryInterface
{
    public function add(Invitation $invitation): Invitation;

    public function findOneByEmailAndOrganization(string $email, Organization $organization): ?Invitation;

    public function findByOrganizationUuid(string $organizationUuid): array;
}
