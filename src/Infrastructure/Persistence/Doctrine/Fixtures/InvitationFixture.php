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
    public const UUID = '53aede0c-1ff3-4873-9e3d-132950dfb893';
    public const INVITATION_ALREADY_JOINED_UUID = 'ad7f73e0-89ab-43ab-8d17-94255d4e57e9';

    public function load(ObjectManager $manager): void
    {
        $invitation = new Invitation(
            uuid: self::UUID,
            fullName: 'Mathieu MARCHOIS',
            email: 'mathieu.marchois@beta.gouv.fr',
            role: OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value,
            createdAt: new \DateTimeImmutable('2025-02-12'),
            owner: $this->getReference('otherOrgUser', User::class),
            organization: $this->getReference('regionIdfOrg', Organization::class),
        );

        $invitationAlreadyJoined = new Invitation(
            uuid: self::INVITATION_ALREADY_JOINED_UUID,
            fullName: 'Mathieu MARCHOIS',
            email: 'mathieu.marchois@beta.gouv.fr',
            role: OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value,
            createdAt: new \DateTimeImmutable('2025-02-12'),
            owner: $this->getReference('department93Admin', User::class),
            organization: $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $manager->persist($invitation);
        $manager->persist($invitationAlreadyJoined);
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
