<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Statistics\Query;

use App\Application\Statistics\Query\GetOrganizationsGeometriesQuery;
use App\Application\Statistics\Query\GetOrganizationsGeometriesQueryHandler;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOrganizationsGeometriesQueryHandlerTest extends TestCase
{
    private OrganizationRepositoryInterface $organizationRepository;

    protected function setUp(): void
    {
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
    }

    public function testWithoutGeometries(): void
    {
        $this->organizationRepository
            ->expects(self::once())
            ->method('findAllForStatistics')
            ->willReturn([]);

        $handler = new GetOrganizationsGeometriesQueryHandler($this->organizationRepository);
        $result = $handler(new GetOrganizationsGeometriesQuery());

        $this->assertEquals([
            'type' => 'FeatureCollection',
            'features' => [],
        ], $result);
    }

    public function testWithGeometries(): void
    {
        $rows = [
            [
                'cluster_id' => 0,
                'geometry' => '{"type":"Polygon","coordinates":[[[2.2,48.8],[2.4,48.8],[2.4,48.9],[2.2,48.9],[2.2,48.8]]]}',
                'cluster_name' => 'Ville de Paris, Ville de Lyon',
            ],
            [
                'cluster_id' => 1,
                'geometry' => '{"type":"Polygon","coordinates":[[[4.8,45.7],[4.9,45.7],[4.9,45.8],[4.8,45.8],[4.8,45.7]]]}',
                'cluster_name' => 'Ville de Marseille',
            ],
        ];

        $this->organizationRepository
            ->expects(self::once())
            ->method('findAllForStatistics')
            ->willReturn($rows);

        $handler = new GetOrganizationsGeometriesQueryHandler($this->organizationRepository);
        $result = $handler(new GetOrganizationsGeometriesQuery());

        $this->assertEquals([
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[[2.2, 48.8], [2.4, 48.8], [2.4, 48.9], [2.2, 48.9], [2.2, 48.8]]],
                    ],
                    'properties' => [
                        'clusterName' => 'Ville de Paris, Ville de Lyon',
                    ],
                ],
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[[4.8, 45.7], [4.9, 45.7], [4.9, 45.8], [4.8, 45.8], [4.8, 45.7]]],
                    ],
                    'properties' => [
                        'clusterName' => 'Ville de Marseille',
                    ],
                ],
            ],
        ], $result);
    }
}
