<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Repository;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RegulationOrderRecordRepositoryTest extends KernelTestCase
{
    private RegulationOrderRecordRepositoryInterface $repository;

    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $this->repository = $container->get(RegulationOrderRecordRepositoryInterface::class);
    }

    public function testCifsFiltersEmpty(): void
    {
        $regulationOrderRecords = $this->repository->findRegulationOrdersForCifsIncidentFormat();
        $this->assertEquals([
            RegulationOrderRecordFixture::UUID_CIFS,
            RegulationOrderRecordFixture::UUID_LITTERALIS,
        ], array_map(fn ($roc) => $roc->getUuid(), $regulationOrderRecords));
        $this->assertEquals(
            [LocationFixture::UUID_CIFS_NAMED_STREET, LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD],
            array_map(fn ($loc) => $loc->getUuid(), $regulationOrderRecords[0]->getRegulationOrder()->getMeasures()[0]->getLocations()->toArray()),
        );
    }

    public function testCifsFiltersAllowedSources(): void
    {
        $regulationOrderRecords = $this->repository->findRegulationOrdersForCifsIncidentFormat(
            allowedSources: [RegulationOrderRecordSourceEnum::DIALOG->value],
        );

        $this->assertEquals([RegulationOrderRecordFixture::UUID_CIFS], array_map(fn ($roc) => $roc->getUuid(), $regulationOrderRecords));
    }

    public function testCifsFiltersExcludedIdentifiers(): void
    {
        $regulationOrderRecords = $this->repository->findRegulationOrdersForCifsIncidentFormat(
            excludedIdentifiers: [RegulationOrderFixture::IDENTIFIER_CIFS],
        );

        $this->assertEquals([RegulationOrderRecordFixture::UUID_LITTERALIS], array_map(fn ($roc) => $roc->getUuid(), $regulationOrderRecords));
    }

    public function testCifsFiltersAllowedLocationIds(): void
    {
        $regulationOrderRecords = $this->repository->findRegulationOrdersForCifsIncidentFormat(allowedLocationIds: [LocationFixture::UUID_CIFS_NAMED_STREET]);
        $this->assertEquals([RegulationOrderRecordFixture::UUID_CIFS], array_map(fn ($roc) => $roc->getUuid(), $regulationOrderRecords));
        $this->assertEquals(
            [LocationFixture::UUID_CIFS_NAMED_STREET],
            array_map(fn ($loc) => $loc->getUuid(), $regulationOrderRecords[0]->getRegulationOrder()->getMeasures()[0]->getLocations()->toArray()),
        );
    }
}
