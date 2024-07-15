<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class OrganizationFixture extends Fixture implements DependentFixtureInterface
{
    public const MAIN_ORG_NAME = 'Main Org';
    public const MAIN_ORG_ID = 'e0d93630-acf7-4722-81e8-ff7d5fa64b66'; // DiaLog
    public const OTHER_ORG_ID = '3c46e94d-7ca2-4253-a9ea-0ce5fdb966a4';

    public function load(ObjectManager $manager): void
    {
        $mainOrg = (new Organization(self::MAIN_ORG_ID))
            ->setName(self::MAIN_ORG_NAME);

        $otherOrg = (new Organization(self::OTHER_ORG_ID))
            ->setName('Mairie de Savenay');

        $organizationUser1 = new OrganizationUser('53aede0c-1ff3-4873-9e3d-132950dfb893');
        $organizationUser1->setUser($this->getReference('mainOrgUser'));
        $organizationUser1->setOrganization($mainOrg);
        $organizationUser1->setRoles([OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR]);

        $organizationUser2 = new OrganizationUser('cf72ca91-3446-410f-b563-74085516180d');
        $organizationUser2->setUser($this->getReference('mainOrgAdmin'));
        $organizationUser2->setOrganization($mainOrg);
        $organizationUser2->setRoles([OrganizationRolesEnum::ROLE_ORGA_ADMIN]);

        $organizationUser3 = new OrganizationUser('890615e1-bcb0-4623-a2fa-362435109030');
        $organizationUser3->setUser($this->getReference('otherOrgUser'));
        $organizationUser3->setOrganization($otherOrg);
        $organizationUser3->setRoles([OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR]);

        $manager->persist($mainOrg);
        $manager->persist($otherOrg);
        $manager->persist($organizationUser1);
        $manager->persist($organizationUser2);
        $manager->persist($organizationUser3);
        $manager->flush();

        $this->addReference('mainOrg', $mainOrg);
        $this->addReference('otherOrg', $otherOrg);
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
