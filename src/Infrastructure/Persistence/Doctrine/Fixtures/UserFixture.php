<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

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
            ->setPassword(self::PASSWORD);

        $mainOtherAdmin = (new User('5bc831a3-7a09-44e9-aefa-5ce3588dac33'))
            ->setFullName('Mathieu FERNANDEZ')
            ->setEmail(self::MAIN_ORG_ADMIN_EMAIL)
            ->setPassword(self::PASSWORD);

        $otherOrgUser = (new User('d47badd9-989e-472b-a80e-9df642e93880'))
            ->setFullName('Florimond MANCA')
            ->setEmail(self::OTHER_ORG_USER_EMAIL)
            ->setPassword(self::PASSWORD);

        $manager->persist($mainOrgUser);
        $manager->persist($mainOtherAdmin);
        $manager->persist($otherOrgUser);
        $manager->flush();

        $this->addReference('mainOrgUser', $mainOrgUser);
        $this->addReference('mainOrgAdmin', $mainOtherAdmin);
        $this->addReference('otherOrgUser', $otherOrgUser);
    }
}
