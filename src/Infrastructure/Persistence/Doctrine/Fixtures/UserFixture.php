<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\PasswordUser;
use App\Domain\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class UserFixture extends Fixture implements DependentFixtureInterface
{
    public const DEPARTMENT_93_USER_EMAIL = 'mathieu.marchois@beta.gouv.fr';
    public const DEPARTMENT_93_ADMIN_EMAIL = 'mathieu.fernandez@beta.gouv.fr';
    public const OTHER_ORG_USER_EMAIL = 'florimond.manca@beta.gouv.fr';
    public const PASSWORD = 'password';

    public function load(ObjectManager $manager): void
    {
        $department93User = (new User('0b507871-8b5e-4575-b297-a630310fc06e'))
            ->setFullName('Mathieu MARCHOIS')
            ->setEmail(self::DEPARTMENT_93_USER_EMAIL)
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate(new \DateTimeImmutable('2024-03-01'))
            ->setLastActiveAt(new \DateTimeImmutable('2024-06-07'));
        $department93UserPassword = new PasswordUser('e06f9972-6add-403d-82d9-bd7370b7668f', self::PASSWORD, $department93User);

        $department93Admin = (new User('5bc831a3-7a09-44e9-aefa-5ce3588dac33'))
            ->setFullName('Mathieu FERNANDEZ')
            ->setEmail(self::DEPARTMENT_93_ADMIN_EMAIL)
            ->setRoles([UserRolesEnum::ROLE_SUPER_ADMIN->value])
            ->setRegistrationDate(new \DateTimeImmutable('2024-04-02'))
            ->setLastActiveAt(new \DateTimeImmutable('2024-06-08'));
        $department93AdminPassword = new PasswordUser('437dd344-0b78-4aa6-ba79-112cb6827516', self::PASSWORD, $department93Admin);

        $otherOrgUser = (new User('d47badd9-989e-472b-a80e-9df642e93880'))
            ->setFullName('Florimond MANCA')
            ->setEmail(self::OTHER_ORG_USER_EMAIL)
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate(new \DateTimeImmutable('2024-05-07'));

        $otherOrgPasswordUser = new PasswordUser('7eb26f55-3029-4a61-b88b-30e2a97806ea', self::PASSWORD, $otherOrgUser);

        $organizationUser1 = new OrganizationUser('53aede0c-1ff3-4873-9e3d-132950dfb893');
        $organizationUser1->setUser($department93User);
        $organizationUser1->setOrganization($this->getReference('seineSaintDenisOrg', Organization::class));
        $organizationUser1->setRoles(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value);

        $organizationUser2 = new OrganizationUser('cf72ca91-3446-410f-b563-74085516180d');
        $organizationUser2->setUser($department93Admin);
        $organizationUser2->setOrganization($this->getReference('seineSaintDenisOrg', Organization::class));
        $organizationUser2->setRoles(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value);

        $organizationUser3 = new OrganizationUser('890615e1-bcb0-4623-a2fa-362435109030');
        $organizationUser3->setUser($otherOrgUser);
        $organizationUser3->setOrganization($this->getReference('regionIdfOrg', Organization::class));
        $organizationUser3->setRoles(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value);

        $organizationUser4 = new OrganizationUser('5d054f87-f55b-49fa-8761-e52e37a44ac0');
        $organizationUser4->setUser($otherOrgUser);
        $organizationUser4->setOrganization($this->getReference('saintOuenOrg', Organization::class));
        $organizationUser4->setRoles(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value);

        $organizationUser5 = new OrganizationUser('b3eef867-7446-4d5a-9336-ab3305515f1d');
        $organizationUser5->setUser($department93User);
        $organizationUser5->setOrganization($this->getReference('dialogOrg', Organization::class));
        $organizationUser5->setRoles(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value);

        $manager->persist($department93User);
        $manager->persist($department93Admin);
        $manager->persist($otherOrgUser);
        $manager->persist($department93UserPassword);
        $manager->persist($department93AdminPassword);
        $manager->persist($otherOrgPasswordUser);
        $manager->persist($organizationUser1);
        $manager->persist($organizationUser2);
        $manager->persist($organizationUser3);
        $manager->persist($organizationUser4);
        $manager->persist($organizationUser5);
        $manager->flush();

        $this->addReference('department93User', $department93User);
        $this->addReference('department93Admin', $department93Admin);
        $this->addReference('otherOrgUser', $otherOrgUser);
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixture::class,
        ];
    }
}
