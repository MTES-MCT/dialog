<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Geography\View\AdministrativeBoundaryView;
use App\Application\Organization\View\MapBboxView;
use App\Domain\Geography\Repository\AdministrativeBoundaryRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AdministrativeBoundaryRepositoryTest extends KernelTestCase
{
    private const LOIRE_ATLANTIQUE = '{"type":"Polygon","coordinates":[[[-2.5,46.9],[-0.9,46.9],[-0.9,47.8],[-2.5,47.8],[-2.5,46.9]]]}';
    private const PAYS_DE_LA_LOIRE = '{"type":"Polygon","coordinates":[[[-2.6,46.3],[0.9,46.3],[0.9,48.6],[-2.6,48.6],[-2.6,46.3]]]}';

    private AdministrativeBoundaryRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get(AdministrativeBoundaryRepositoryInterface::class);
    }

    public function testFindOneByCodeReturnsNullWhenNotStored(): void
    {
        $this->assertNull($this->repository->findOneByCode('departement', '44'));
        $this->assertNull($this->repository->findMapBbox('departement', '44'));
        $this->assertSame([], $this->repository->findAllViews());
    }

    public function testSaveThenFind(): void
    {
        $this->repository->save('region', '52', 'Pays de la Loire', self::PAYS_DE_LA_LOIRE, new \DateTimeImmutable('2026-09-30'));
        $this->repository->save('departement', '44', 'Loire-Atlantique', self::LOIRE_ATLANTIQUE, new \DateTimeImmutable('2026-09-30'));

        $this->assertEquals(
            new AdministrativeBoundaryView('departement', '44', 'Loire-Atlantique'),
            $this->repository->findOneByCode('departement', '44'),
        );
        // Le type fait partie de l'identifiant : le code 44 est aussi celui de la région Grand Est.
        $this->assertNull($this->repository->findOneByCode('region', '44'));

        $this->assertEquals(
            [
                new AdministrativeBoundaryView('departement', '44', 'Loire-Atlantique'),
                new AdministrativeBoundaryView('region', '52', 'Pays de la Loire'),
            ],
            $this->repository->findAllViews(),
        );

        $this->assertEquals(
            new MapBboxView(minLon: -2.5, minLat: 46.9, maxLon: -0.9, maxLat: 47.8),
            $this->repository->findMapBbox('departement', '44'),
        );
    }

    public function testSaveUpdatesExistingBoundary(): void
    {
        $this->repository->save('departement', '44', 'Loire-Inférieure', self::PAYS_DE_LA_LOIRE, new \DateTimeImmutable('2025-01-01'));
        $this->repository->save('departement', '44', 'Loire-Atlantique', self::LOIRE_ATLANTIQUE, new \DateTimeImmutable('2026-09-30'));

        $this->assertEquals(
            [new AdministrativeBoundaryView('departement', '44', 'Loire-Atlantique')],
            $this->repository->findAllViews(),
        );
        $this->assertEquals(
            new MapBboxView(minLon: -2.5, minLat: 46.9, maxLon: -0.9, maxLat: 47.8),
            $this->repository->findMapBbox('departement', '44'),
        );
    }

    public function testDelete(): void
    {
        $this->repository->save('departement', '44', 'Loire-Atlantique', self::LOIRE_ATLANTIQUE, new \DateTimeImmutable('2026-09-30'));
        $this->repository->save('region', '52', 'Pays de la Loire', self::PAYS_DE_LA_LOIRE, new \DateTimeImmutable('2026-09-30'));

        $this->repository->delete('departement', '44');

        $this->assertNull($this->repository->findOneByCode('departement', '44'));
        $this->assertNotNull($this->repository->findOneByCode('region', '52'));
    }
}
