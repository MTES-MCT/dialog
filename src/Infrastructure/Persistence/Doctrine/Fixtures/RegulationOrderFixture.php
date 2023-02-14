<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\RegulationOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $regulationOrder1 = new RegulationOrder(
            uuid: 'e413a47e-5928-4353-a8b2-8b7dda27f9a5',
            issuingAuthority: 'Autorité 1',
            description: 'Description 1',
            regulationOrderRecord: $this->getReference('regulationOrderRecord1'),
            regulationCondition: $this->getReference('regulationCondition1'),
        );

        $regulationOrder2 = new RegulationOrder(
            uuid: '3ede8b1a-1816-4788-8510-e08f45511cb5',
            issuingAuthority: 'Autorité 2',
            description: 'Description 2',
            regulationOrderRecord: $this->getReference('regulationOrderRecord2'),
            regulationCondition: $this->getReference('regulationCondition2'),
        );

        $regulationOrder3 = new RegulationOrder(
            uuid: '4ce75a1f-82f3-40ee-8f95-48d0f04446aa',
            issuingAuthority: 'Description 3',
            description: 'Autorité 3',
            regulationOrderRecord: $this->getReference('regulationOrderRecord3'),
            regulationCondition: $this->getReference('regulationCondition3'),
        );

        $regulationOrder4 = new RegulationOrder(
            uuid: '867d2be6-0d80-41b5-b1ff-8452b30a95f5',
            issuingAuthority: 'Description 4',
            description: 'Autorité 4',
            regulationOrderRecord: $this->getReference('regulationOrderRecord4'),
            regulationCondition: $this->getReference('regulationCondition4'),
        );

        $manager->persist($regulationOrder1);
        $manager->persist($regulationOrder2);
        $manager->persist($regulationOrder3);
        $manager->persist($regulationOrder4);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderRecordFixture::class,
            RegulationConditionFixture::class,
        ];
    }
}
