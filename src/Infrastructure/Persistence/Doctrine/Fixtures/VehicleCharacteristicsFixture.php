<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Condition\Enum\VehicleCritairEnum;
use App\Domain\Condition\Enum\VehicleTypeEnum;
use App\Domain\Condition\Enum\VehicleUsageEnum;
use App\Domain\Condition\VehicleCharacteristics;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class VehicleCharacteristicsFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $vehicleCharacteristics = new VehicleCharacteristics(
            'c58b3806-cb6e-461d-b03e-473e3b0a0e3c',
            $this->getReference('regulationCondition2'),
            VehicleUsageEnum::NON_COMMERCIAL,
            VehicleTypeEnum::ELECTRIC_VEHICLES,
            VehicleCritairEnum::V4,
            3.5,
            1.8,
            2.0,
            6.0,
        );

        $manager->persist($vehicleCharacteristics);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationConditionFixture::class,
        ];
    }
}
