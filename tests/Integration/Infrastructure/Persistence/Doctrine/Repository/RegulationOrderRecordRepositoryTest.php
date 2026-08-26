<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class RegulationOrderRecordRepositoryTest extends AbstractWebTestCase
{
    private RegulationOrderRecordRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get(RegulationOrderRecordRepositoryInterface::class);
    }

    public function testCountRegulationsByStatusForOrganizations(): void
    {
        $counts = $this->repository->countRegulationsByStatusForOrganizations(
            [OrganizationFixture::SEINE_SAINT_DENIS_ID],
            new \DateTimeImmutable('2023-03-15 00:00:00+00:00'),
        );

        // Brouillons : typical, permanent, fullCity, noLocations, noMeasures, rawGeoJSON.
        // En vigueur au 15/03/2023 : publishedRegulationOrder (10/03 → 28/03).
        // À venir : CIFS (05/06/2023) et Litteralis (03/07/2023).
        $this->assertSame(['draftCount' => 6, 'currentCount' => 1, 'upcomingCount' => 2], $counts);
    }

    public function testCountRegulationsByStatusForOrganizationsAllUpcoming(): void
    {
        $counts = $this->repository->countRegulationsByStatusForOrganizations(
            [OrganizationFixture::SEINE_SAINT_DENIS_ID],
            new \DateTimeImmutable('2023-01-01 00:00:00+00:00'),
        );

        $this->assertSame(['draftCount' => 6, 'currentCount' => 0, 'upcomingCount' => 3], $counts);
    }

    public function testCountRegulationsByStatusForOrganizationsAllExpired(): void
    {
        $counts = $this->repository->countRegulationsByStatusForOrganizations(
            [OrganizationFixture::SEINE_SAINT_DENIS_ID, OrganizationFixture::DIALOG_ORG_ID],
            new \DateTimeImmutable('2026-01-01 00:00:00+00:00'),
        );

        $this->assertSame(['draftCount' => 7, 'currentCount' => 0, 'upcomingCount' => 0], $counts);
    }

    public function testCountRegulationsByStatusForOrganizationsWithoutOrganizations(): void
    {
        $counts = $this->repository->countRegulationsByStatusForOrganizations(
            [],
            new \DateTimeImmutable('2023-03-15 00:00:00+00:00'),
        );

        $this->assertSame(['draftCount' => 0, 'currentCount' => 0, 'upcomingCount' => 0], $counts);
    }

    public function testFindLatestRegulations(): void
    {
        $rows = $this->repository->findLatestRegulations([OrganizationFixture::SEINE_SAINT_DENIS_ID], 4);

        // Sans historique, le tri retombe sur la date de création (createdAt),
        // puis sur l'identifiant en cas d'égalité.
        $this->assertSame(
            ['117374#24-A-0473', 'F/CIFS/2023', 'F2023/full-city', 'FO3/2023'],
            array_column($rows, 'identifier'),
        );
    }

    public function testFindLatestRegulationsRespectsMaxResults(): void
    {
        $rows = $this->repository->findLatestRegulations([OrganizationFixture::SEINE_SAINT_DENIS_ID], 2);

        $this->assertSame(['117374#24-A-0473', 'F/CIFS/2023'], array_column($rows, 'identifier'));
    }

    public function testFindLatestRegulationsWithoutOrganizations(): void
    {
        $this->assertSame([], $this->repository->findLatestRegulations([], 4));
    }
}
