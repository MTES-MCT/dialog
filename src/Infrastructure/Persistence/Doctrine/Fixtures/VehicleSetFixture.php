<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class VehicleSetFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $typicalVehicleSet = new VehicleSet(
            '6a65132f-7319-4d5a-aa2d-479685d49df3',
            measure: $this->getReference('typicalMeasure'),
            restrictedTypes: [],
            exemptedTypes: [],
            otherExemptedTypeText: null,
        );

        $complexVehicleSet = new VehicleSet(
            '981f0260-948e-45e9-8788-efa23859a884',
            measure: $this->getReference('measureWithComplexVehicleSet'),
            restrictedTypes: [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, VehicleTypeEnum::DIMENSIONS->value, VehicleTypeEnum::CRITAIR->value],
            exemptedTypes: [VehicleTypeEnum::PEDESTRIANS->value, VehicleTypeEnum::EMERGENCY_SERVICES->value, VehicleTypeEnum::OTHER->value],
            otherExemptedTypeText: 'Convois exceptionnels',
            heavyweightMaxWeight: 3.5,
            maxLength: 12,
            maxHeight: 2.4,
            critairTypes: [CritairEnum::CRITAIR_4->value, CritairEnum::CRITAIR_5->value],
        );

        $publishedLocation1Measure2VehicleSet = new VehicleSet(
            '064ca7cf-a825-7e27-8000-42b09e3ccf61',
            measure: $this->getReference('publishedLocation1Measure2'),
            restrictedTypes: [VehicleTypeEnum::CRITAIR->value],
            critairTypes: [CritairEnum::CRITAIR_3->value],
        );

        $permanentMeasure1VehicleSet = new VehicleSet(
            '065001b9-28f5-79ad-8000-713cf4da4603',
            measure: $this->getReference('permanentRegulationOrderLocationMeasure1'),
            restrictedTypes: [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value],
            heavyweightMaxWeight: 3.5,
        );

        $manager->persist($typicalVehicleSet);
        $manager->persist($complexVehicleSet);
        $manager->persist($publishedLocation1Measure2VehicleSet);
        $manager->persist($permanentMeasure1VehicleSet);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MeasureFixture::class,
        ];
    }
}
