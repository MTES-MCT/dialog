<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Organization\ApiClient;
use App\Domain\User\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ApiClientFixture extends Fixture implements DependentFixtureInterface
{
    public const SEINE_SAINT_DENIS_API_CLIENT_UUID = '0b507871-8b5e-4575-b297-a630310fc06e';
    public const SAINT_OUEN_API_CLIENT_UUID = '0ae55b60-455a-449b-a2b7-26ea38d909f2';

    public function load(ObjectManager $manager): void
    {
        $seineSaintDenisClient = (new ApiClient(self::SEINE_SAINT_DENIS_API_CLIENT_UUID))
            ->setClientId('clientId')
            ->setClientSecret('clientSecret')
            ->setOrganization($this->getReference('seineSaintDenisOrg', Organization::class))
            ->setCreatedAt(new \DateTimeImmutable('2024-03-01'))
            ->setIsActive(true);

        $saintOuenOrgClient = (new ApiClient(self::SAINT_OUEN_API_CLIENT_UUID))
            ->setClientId('saintOuenOrgClientId')
            ->setClientSecret('saintOuenOrgClientSecret')
            ->setOrganization($this->getReference('saintOuenOrg', Organization::class))
            ->setCreatedAt(new \DateTimeImmutable('2025-03-01'))
            ->setIsActive(true);

        $manager->persist($seineSaintDenisClient);
        $manager->persist($saintOuenOrgClient);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixture::class,
        ];
    }
}
