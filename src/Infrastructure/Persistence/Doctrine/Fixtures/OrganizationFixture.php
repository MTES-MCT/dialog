<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class OrganizationFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $organization1 = new Organization(
            '0eed3bec-7fe0-469b-a3e9-1c24251bf48c',
            'DiaLog',
        );
        $organization1->addUser($this->getReference('user1'));

        $organization2 = new Organization(
            '3c46e94d-7ca2-4253-a9ea-0ce5fdb966a4',
            'Mairie de Savenay',
        );
        $organization2->addUser($this->getReference('user2'));

        $manager->persist($organization1);
        $manager->persist($organization2);
        $manager->flush();

        $this->addReference('organization1', $organization1);
        $this->addReference('organization2', $organization2);
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
