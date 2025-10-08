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
    public function load(ObjectManager $manager): void
    {
        $seineSaintDenisClient = (new ApiClient('0b507871-8b5e-4575-b297-a630310fc06e'))
            ->setClientId('clientId')
            ->setClientSecret('clientSecret')
            ->setOrganization($this->getReference('seineSaintDenisOrg', Organization::class))
            ->setCreatedAt(new \DateTimeImmutable('2024-03-01'))
            ->setIsActive(true);

        $manager->persist($seineSaintDenisClient);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixture::class,
        ];
    }
}
