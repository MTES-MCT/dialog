<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Organization\View\MapBboxView;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class OrganizationRepositoryTest extends AbstractWebTestCase
{
    private OrganizationRepositoryInterface $organizationRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->organizationRepository = $container->get(OrganizationRepositoryInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        // Make sure the cache table starts empty (other tests might have populated it).
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE top_published_organization');
    }

    public function testFindInitialMapBboxForUserReturnsBboxOfFirstOrganization(): void
    {
        // department93User belongs to seineSaintDenisOrg (which has a geometry)
        // and to dialogOrg (which has none).
        $bbox = $this->organizationRepository->findInitialMapBbox('0b507871-8b5e-4575-b297-a630310fc06e');

        $this->assertInstanceOf(MapBboxView::class, $bbox);
        // The Seine-Saint-Denis bounding box, roughly: lon ~[2.28, 2.60], lat ~[48.80, 49.01].
        $this->assertGreaterThan(2.0, $bbox->minLon);
        $this->assertLessThan(3.0, $bbox->maxLon);
        $this->assertGreaterThan(48.0, $bbox->minLat);
        $this->assertLessThan(49.5, $bbox->maxLat);
        $this->assertLessThan($bbox->maxLon, $bbox->minLon);
        $this->assertLessThan($bbox->maxLat, $bbox->minLat);
    }

    public function testFindInitialMapBboxForUnknownUserReturnsNull(): void
    {
        $bbox = $this->organizationRepository->findInitialMapBbox('00000000-0000-0000-0000-000000000000');

        $this->assertNull($bbox);
    }

    public function testFindInitialMapBboxAnonymousReturnsNullWhenCacheEmpty(): void
    {
        $bbox = $this->organizationRepository->findInitialMapBbox(null);

        $this->assertNull($bbox);
    }

    public function testRefreshTopPublishedOrganizationsAndPickRandom(): void
    {
        $this->organizationRepository->refreshTopPublishedOrganizations();

        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT COUNT(*) AS n FROM top_published_organization',
        );
        $this->assertGreaterThan(0, (int) $row['n']);

        // The DiaLog org must be excluded from the cache.
        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT COUNT(*) AS n FROM top_published_organization WHERE organization_uuid = :uuid',
            ['uuid' => OrganizationFixture::DIALOG_ORG_ID],
        );
        $this->assertSame(0, (int) $row['n']);

        // Anonymous bbox is now populated by the cache.
        $bbox = $this->organizationRepository->findInitialMapBbox(null);
        $this->assertInstanceOf(MapBboxView::class, $bbox);
        $this->assertLessThan($bbox->maxLon, $bbox->minLon);
        $this->assertLessThan($bbox->maxLat, $bbox->minLat);
    }

    public function testRefreshTopPublishedOrganizationsRespectsLimit(): void
    {
        $this->organizationRepository->refreshTopPublishedOrganizations(limit: 1);

        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT COUNT(*) AS n FROM top_published_organization',
        );
        $this->assertSame(1, (int) $row['n']);
    }

    public function testRefreshTopPublishedOrganizationsIsIdempotent(): void
    {
        $this->organizationRepository->refreshTopPublishedOrganizations();
        $first = (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM top_published_organization',
        );

        $this->organizationRepository->refreshTopPublishedOrganizations();
        $second = (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM top_published_organization',
        );

        $this->assertSame($first, $second);

        // Sanity-check: UserFixture::DEPARTMENT_93_USER_EMAIL is unrelated; just keep it referenced
        // to ensure fixture stays loaded in this suite.
        $this->assertNotSame('', UserFixture::DEPARTMENT_93_USER_EMAIL);
    }
}
