<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

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
    public const INVITATION_FOR_NEW_USER_UUID = 'b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e';
    public const INVITATION_MANDATAIRE_UUID = 'e7c9a2b4-1d3f-4a5b-8c6d-9e0f1a2b3c4d';

    public function load(ObjectManager $manager): void
    {
        $invitation = new Invitation(
            uuid: self::UUID,
            fullName: 'Mathieu MARCHOIS',
            email: 'mathieu.marchois@beta.gouv.fr',
            createdAt: new \DateTimeImmutable('2025-02-12'),
            owner: $this->getReference('otherOrgUser', User::class),
            organization: $this->getReference('regionIdfOrg', Organization::class),
        );

        $invitationAlreadyJoined = new Invitation(
            uuid: self::INVITATION_ALREADY_JOINED_UUID,
            fullName: 'Mathieu MARCHOIS',
            email: 'mathieu.marchois@beta.gouv.fr',
            createdAt: new \DateTimeImmutable('2025-02-12'),
            owner: $this->getReference('department93Admin', User::class),
            organization: $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $invitationForNewUser = new Invitation(
            uuid: self::INVITATION_FOR_NEW_USER_UUID,
            fullName: 'Nouveau Utilisateur',
            email: 'nouveau.utilisateur@example.com',
            createdAt: new \DateTimeImmutable('2025-02-12'),
            owner: $this->getReference('department93Admin', User::class),
            organization: $this->getReference('dialogOrg', Organization::class),
        );

        $invitationMandataire = new Invitation(
            uuid: self::INVITATION_MANDATAIRE_UUID,
            fullName: 'Marc PRESTATAIRE',
            email: 'marc.prestataire@example.com',
            createdAt: new \DateTimeImmutable('2025-02-11'),
            owner: $this->getReference('department93Admin', User::class),
            organization: $this->getReference('seineSaintDenisOrg', Organization::class),
            isMandataire: true,
        );

        $manager->persist($invitation);
        $manager->persist($invitationAlreadyJoined);
        $manager->persist($invitationForNewUser);
        $manager->persist($invitationMandataire);
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
