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

        // Document rattaché à un arrêté publié, sans fichier téléversé : seule l'URL
        // externe est disponible (cas couvert par l'API de recherche).
        $storageRegulationOrderCifs = new StorageRegulationOrder(
            uuid: '2a2fac83-b2f7-4d47-9273-e2454b2b863d',
            regulationOrder: $this->getReference('regulationOrderCifs', RegulationOrder::class),
            path: null,
            url: 'https://example.com/arrete-cifs.pdf',
            title: 'Arrêté CIFS',
        );

        $manager->persist($storageRegulationOrder);
        $manager->persist($storageRegulationOrderCifs);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
