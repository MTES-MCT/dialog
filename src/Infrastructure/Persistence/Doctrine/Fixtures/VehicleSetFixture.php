<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Condition\VehicleSet;
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
            restrictedTypes: [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, VehicleTypeEnum::CRITAIR_5->value],
            exemptedTypes: [VehicleTypeEnum::PEDESTRIANS->value, VehicleTypeEnum::AMBULANCE->value, VehicleTypeEnum::OTHER->value],
            otherExemptedTypeText: 'Convois exceptionnels',
        );
        $vehicleSet2 = new VehicleSet(
            '6a65132f-7319-4d5a-aa2d-479685d49df3',
            measure: $this->getReference('measure3'),
            restrictedTypes: [],
            exemptedTypes: [],
            otherExemptedTypeText: null,
        );

        $manager->persist($vehicleSet1);
        $manager->persist($vehicleSet2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MeasureFixture::class,
        ];
    }
}
