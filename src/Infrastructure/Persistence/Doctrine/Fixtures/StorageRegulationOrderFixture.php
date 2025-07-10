<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\StorageRegulationOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class StorageRegulationOrderFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $storageRegulationOrder = new StorageRegulationOrder(
            uuid: 'b778ec26-f120-4768-ba05-c55d781d8ada',
            regulationOrder: $this->getReference('typicalRegulationOrder', RegulationOrder::class),
            path: '/files/storage1.pdf',
            url: 'https://example.com/storage1.pdf',
            title: 'Arrêté test 2025-06',
        );

        $manager->persist($storageRegulationOrder);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
