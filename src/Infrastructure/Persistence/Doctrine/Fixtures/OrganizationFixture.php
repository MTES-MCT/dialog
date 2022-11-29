<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class EventFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $organization = new Organization(
            'f693fa8a-8c6e-4e0d-b686-98382ccc6d49',
            'Commune de Savenay',
        );
        $organization->addUser($this->getReference(UserFixture::DIALOG_ADMIN_USER));

        $manager->persist($organization);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
