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
        $vehicleSet1 = new VehicleSet(
            '981f0260-948e-45e9-8788-efa23859a884',
            measure: $this->getReference('measure4'),
            restrictedTypes: [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, VehicleTypeEnum::CRITAIR->value],
            exemptedTypes: [VehicleTypeEnum::PEDESTRIANS->value, VehicleTypeEnum::AMBULANCE->value, VehicleTypeEnum::OTHER->value],
            otherExemptedTypeText: 'Convois exceptionnels',
            heavyweightMaxWeight: 3.5,
            heavyweightMaxLength: 12,
            heavyweightMaxHeight: 2.4,
            critairTypes: [CritairEnum::CRITAIR_4->value, CritairEnum::CRITAIR_5->value],
        );

        $vehicleSet2 = new VehicleSet(
            '6a65132f-7319-4d5a-aa2d-479685d49df3',
            measure: $this->getReference('measure3'),
            restrictedTypes: [],
            exemptedTypes: [],
            otherExemptedTypeText: null,
        );

        $vehicleSet3 = new VehicleSet(
            '064ca7cf-a825-7e27-8000-42b09e3ccf61',
            measure: $this->getReference('measure5'),
            restrictedTypes: [VehicleTypeEnum::CRITAIR->value],
            critairTypes: [CritairEnum::CRITAIR_3->value],
        );

        $vehicleSet4 = new VehicleSet(
            '065001b9-28f5-79ad-8000-713cf4da4603',
            measure: $this->getReference('measure1'),
            restrictedTypes: [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value],
            heavyweightMaxWeight: 3.5,
        );

        $manager->persist($vehicleSet1);
        $manager->persist($vehicleSet2);
        $manager->persist($vehicleSet3);
        $manager->persist($vehicleSet4);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MeasureFixture::class,
        ];
    }
}
