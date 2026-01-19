<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderRecordFixture extends Fixture implements DependentFixtureInterface
{
    public const UUID_DOES_NOT_EXIST = '0658c5da-0471-72c0-8000-b112b4561ea1';

    public const UUID_TYPICAL = 'e413a47e-5928-4353-a8b2-8b7dda27f9a5';

    public const UUID_PUBLISHED = '3ede8b1a-1816-4788-8510-e08f45511cb5';
    public const UUID_COMPLEX_VEHICLES = self::UUID_PUBLISHED;
    public const UUID_DUPLICATE_NAME_CONFLICT = self::UUID_PUBLISHED;

    public const UUID_OTHER_ORG = '867d2be6-0d80-41b5-b1ff-8452b30a95f5';
    public const UUID_OTHER_ORG_NO_START_DATE = self::UUID_OTHER_ORG;

    public const UUID_PERMANENT = '4ce75a1f-82f3-40ee-8f95-48d0f04446aa';
    public const UUID_ONLY_ONE_LOCATION = self::UUID_PERMANENT;

    public const UUID_FULL_CITY = '0658c565-c783-7de3-8000-a903e5b4d9d4';

    public const UUID_NO_LOCATIONS = 'b1a3e982-39a1-4f0e-8a6f-ea2fd5e872c2';
    public const UUID_LONG_DESCRIPTION = self::UUID_NO_LOCATIONS;
    public const UUID_OTHER_CATEGORY = self::UUID_NO_LOCATIONS;

    public const UUID_NO_MEASURES = '0650037d-3e90-7a99-8000-a2099e71ae4a';
    public const UUID_CIFS = '0654905d-6771-75d8-8000-d523184d0b55';
    public const UUID_RAWGEOJSON = '06672e5e-8322-739f-8000-9ebfcd86e29a';
    public const UUID_LITTERALIS = '066e9849-1457-7a1e-8000-3142ece4a7de';
    public const UUID_WINTER_MAINTENANCE = '6f665f38-7765-47b1-849a-06279eba3ac6';
    public const UUID_PARKING_PROHIBITED = '9995801a-0a5c-4a93-8562-f84df8484315';

    public function load(ObjectManager $manager): void
    {
        $typicalRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_TYPICAL,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT->value,
            $this->getReference('typicalRegulationOrder', RegulationOrder::class),
            new \DateTimeImmutable('2022-01-10'),
            $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $publishedRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_PUBLISHED,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::PUBLISHED->value,
            $this->getReference('publishedRegulationOrder', RegulationOrder::class),
            new \DateTimeImmutable('2022-01-10'),
            $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $regulationOrderRecordPermanent = new RegulationOrderRecord(
            self::UUID_PERMANENT,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT->value,
            $this->getReference('regulationOrderPermanent', RegulationOrder::class),
            new \DateTimeImmutable('2022-01-11'),
            $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $fullCityRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_FULL_CITY,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT->value,
            $this->getReference('fullCityRegulationOrder', RegulationOrder::class),
            new \DateTimeImmutable('2022-01-11'),
            $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $regulationOrderRecordNoLocations = new RegulationOrderRecord(
            self::UUID_NO_LOCATIONS,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT->value,
            $this->getReference('regulationOrderNoLocations', RegulationOrder::class),
            new \DateTimeImmutable('2022-01-10'),
            $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $regulationOrderRecordNoMeasures = new RegulationOrderRecord(
            self::UUID_NO_MEASURES,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT->value,
            $this->getReference('regulationOrderNoMeasures', RegulationOrder::class),
            new \DateTimeImmutable('2022-01-10'),
            $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $regulationOrderRecordCifs = new RegulationOrderRecord(
            self::UUID_CIFS,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::PUBLISHED->value,
            $this->getReference('regulationOrderCifs', RegulationOrder::class),
            new \DateTimeImmutable('2023-09-06'),
            $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $outDatedRegulationOrderRecordCifs = new RegulationOrderRecord(
            '9d408332-d30f-4530-be66-dfb2d98ebae5',
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::PUBLISHED->value,
            $this->getReference('outDatedRegulationOrderCifs', RegulationOrder::class),
            new \DateTimeImmutable('2021-11-02'),
            $this->getReference('regionIdfOrg', Organization::class),
        );

        $rawGeoJSONRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_RAWGEOJSON,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT->value,
            $this->getReference('rawGeoJSONRegulationOrder', RegulationOrder::class),
            new \DateTimeImmutable('2020-06-05'),
            $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $litteralisRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_LITTERALIS,
            RegulationOrderRecordSourceEnum::LITTERALIS->value,
            RegulationOrderRecordStatusEnum::PUBLISHED->value,
            $this->getReference('litteralisRegulationOrder', RegulationOrder::class),
            new \DateTimeImmutable('2024-09-05'),
            $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $winterMaintenanceRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_WINTER_MAINTENANCE,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT->value,
            $this->getReference('winterMaintenanceRegulationOrder', RegulationOrder::class),
            new \DateTimeImmutable('2025-01-08'),
            $this->getReference('dialogOrg', Organization::class),
        );

        $parkingProhibitedRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_PARKING_PROHIBITED,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::PUBLISHED->value,
            $this->getReference('parkingProhibitedRegulationOrder', RegulationOrder::class),
            new \DateTimeImmutable('2025-01-08'),
            $this->getReference('dialogOrg', Organization::class),
        );

        $manager->persist($typicalRegulationOrderRecord);
        $manager->persist($publishedRegulationOrderRecord);
        $manager->persist($regulationOrderRecordPermanent);
        $manager->persist($fullCityRegulationOrderRecord);
        $manager->persist($regulationOrderRecordNoLocations);
        $manager->persist($regulationOrderRecordNoMeasures);
        $manager->persist($regulationOrderRecordCifs);
        $manager->persist($outDatedRegulationOrderRecordCifs);
        $manager->persist($rawGeoJSONRegulationOrderRecord);
        $manager->persist($litteralisRegulationOrderRecord);
        $manager->persist($winterMaintenanceRegulationOrderRecord);
        $manager->persist($parkingProhibitedRegulationOrderRecord);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
            OrganizationFixture::class,
        ];
    }
}
