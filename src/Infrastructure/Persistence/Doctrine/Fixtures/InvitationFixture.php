<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Invitation;
use App\Domain\User\Organization;
use App\Domain\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class InvitationFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $invitation = new Invitation(
            uuid: '53aede0c-1ff3-4873-9e3d-132950dfb893',
            fullName: 'Jean Michel',
            email: 'jean.michel@beta.gouv.fr',
            role: OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value,
            createdAt: new \DateTimeImmutable('2025-02-12'),
            owner: $this->getReference('mainOrgUser', User::class),
            organization: $this->getReference('mainOrg', Organization::class),
        );

        $manager->persist($invitation);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
            OrganizationFixture::class,
        ];
    }
}
