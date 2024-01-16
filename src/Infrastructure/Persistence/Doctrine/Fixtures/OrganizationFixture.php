<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\Organization;
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
        $mainOrg->addUser($this->getReference('mainOrgUser'));
        $mainOrg->addUser($this->getReference('mainOrgAdmin'));

        $otherOrg = (new Organization(self::OTHER_ORG_ID))
            ->setName('Mairie de Savenay');
        $otherOrg->addUser($this->getReference('otherOrgUser'));

        $manager->persist($mainOrg);
        $manager->persist($otherOrg);
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
