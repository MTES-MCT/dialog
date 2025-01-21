<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\PasswordUser;
use App\Domain\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixture extends Fixture
{
    public const MAIN_ORG_USER_EMAIL = 'mathieu.marchois@beta.gouv.fr';
    public const MAIN_ORG_ADMIN_EMAIL = 'mathieu.fernandez@beta.gouv.fr';
    public const OTHER_ORG_USER_EMAIL = 'florimond.manca@beta.gouv.fr';
    public const PASSWORD = 'password';

    public function load(ObjectManager $manager): void
    {
        $mainOrgUser = (new User('0b507871-8b5e-4575-b297-a630310fc06e'))
            ->setFullName('Mathieu MARCHOIS')
            ->setEmail(self::MAIN_ORG_USER_EMAIL)
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate(new \DateTimeImmutable('2024-03-01'))
            ->setLastActiveAt(new \DateTimeImmutable('2024-06-07'));

        $mainOrgPasswordUser = new PasswordUser(
            'e06f9972-6add-403d-82d9-bd7370b7668f',
            self::PASSWORD,
            $mainOrgUser,
        );

        $mainOtherAdmin = (new User('5bc831a3-7a09-44e9-aefa-5ce3588dac33'))
            ->setFullName('Mathieu FERNANDEZ')
            ->setEmail(self::MAIN_ORG_ADMIN_EMAIL)
            ->setRoles([UserRolesEnum::ROLE_SUPER_ADMIN->value])
            ->setRegistrationDate(new \DateTimeImmutable('2024-04-02'))
            ->setLastActiveAt(new \DateTimeImmutable('2024-06-08'));

        $mainOtherPasswordAdmin = new PasswordUser(
            '437dd344-0b78-4aa6-ba79-112cb6827516',
            self::PASSWORD,
            $mainOtherAdmin,
        );

        $otherOrgUser = (new User('d47badd9-989e-472b-a80e-9df642e93880'))
            ->setFullName('Florimond MANCA')
            ->setEmail(self::OTHER_ORG_USER_EMAIL)
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate(new \DateTimeImmutable('2024-05-07'));

        $otherOrgPasswordUser = new PasswordUser(
            '7eb26f55-3029-4a61-b88b-30e2a97806ea',
            self::PASSWORD,
            $otherOrgUser,
        );

        $manager->persist($mainOrgUser);
        $manager->persist($mainOtherAdmin);
        $manager->persist($otherOrgUser);
        $manager->persist($mainOrgPasswordUser);
        $manager->persist($mainOtherPasswordAdmin);
        $manager->persist($otherOrgPasswordUser);
        $manager->flush();

        $this->addReference('mainOrgUser', $mainOrgUser);
        $this->addReference('mainOrgAdmin', $mainOtherAdmin);
        $this->addReference('otherOrgUser', $otherOrgUser);
    }
}
