<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;
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

    public function load(ObjectManager $manager): void
    {
        $typicalRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_TYPICAL,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('typicalRegulationOrder'),
            new \DateTime('2022-01-10'),
            $this->getReference('mainOrg'),
        );

        $publishedRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_PUBLISHED,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::PUBLISHED,
            $this->getReference('publishedRegulationOrder'),
            new \DateTime('2022-01-10'),
            $this->getReference('mainOrg'),
        );

        $regulationOrderRecordDuplicate = new RegulationOrderRecord(
            '0658c6bb-045e-74fd-8000-bc704e4e72cb',
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::PUBLISHED,
            $this->getReference('regulationOrderDuplicate'),
            new \DateTime('2022-01-10'),
            $this->getReference('mainOrg'),
        );

        $regulationOrderRecordPermanent = new RegulationOrderRecord(
            self::UUID_PERMANENT,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('regulationOrderPermanent'),
            new \DateTime('2022-01-11'),
            $this->getReference('mainOrg'),
        );

        $otherOrgRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_OTHER_ORG,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('otherOrgRegulationOrder'),
            new \DateTime('2022-01-11'),
            $this->getReference('otherOrg'),
        );

        $fullCityRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_FULL_CITY,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('fullCityRegulationOrder'),
            new \DateTime('2022-01-11'),
            $this->getReference('mainOrg'),
        );

        $regulationOrderRecordNoLocations = new RegulationOrderRecord(
            self::UUID_NO_LOCATIONS,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('regulationOrderNoLocations'),
            new \DateTime('2022-01-10'),
            $this->getReference('mainOrg'),
        );

        $regulationOrderRecordNoMeasures = new RegulationOrderRecord(
            self::UUID_NO_MEASURES,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('regulationOrderNoMeasures'),
            new \DateTime('2022-01-10'),
            $this->getReference('mainOrg'),
        );

        $regulationOrderRecordCifs = new RegulationOrderRecord(
            '0654905d-6771-75d8-8000-d523184d0b55',
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::PUBLISHED,
            $this->getReference('regulationOrderCifs'),
            new \DateTime('2021-11-02'),
            $this->getReference('mainOrg'),
        );

        $manager->persist($typicalRegulationOrderRecord);
        $manager->persist($publishedRegulationOrderRecord);
        $manager->persist($regulationOrderRecordDuplicate);
        $manager->persist($regulationOrderRecordPermanent);
        $manager->persist($otherOrgRegulationOrderRecord);
        $manager->persist($fullCityRegulationOrderRecord);
        $manager->persist($regulationOrderRecordNoLocations);
        $manager->persist($regulationOrderRecordNoMeasures);
        $manager->persist($regulationOrderRecordCifs);
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
