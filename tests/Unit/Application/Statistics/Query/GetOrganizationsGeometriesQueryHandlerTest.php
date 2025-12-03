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
        $organizations = [
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'name' => 'Ville de Paris',
                'code' => '75056',
                'code_type' => 'INSEE',
                'department_name' => 'Paris',
                'department_code' => '75',
                'geometry' => '{"type":"Point","coordinates":[2.3522,48.8566]}',
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440001',
                'name' => 'Ville de Lyon',
                'code' => '69123',
                'code_type' => 'INSEE',
                'department_name' => 'Rhône',
                'department_code' => '69',
                'geometry' => '{"type":"Point","coordinates":[4.8357,45.7640]}',
            ],
        ];

        $this->organizationRepository
            ->expects(self::once())
            ->method('findAllForStatistics')
            ->willReturn($organizations);

        $handler = new GetOrganizationsGeometriesQueryHandler($this->organizationRepository);
        $result = $handler(new GetOrganizationsGeometriesQuery());

        $this->assertEquals([
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [2.3522, 48.8566],
                    ],
                    'properties' => [
                        'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'name' => 'Ville de Paris',
                        'code' => '75056',
                        'codeType' => 'INSEE',
                        'departmentName' => 'Paris',
                        'departmentCode' => '75',
                    ],
                ],
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [4.8357, 45.7640],
                    ],
                    'properties' => [
                        'uuid' => '550e8400-e29b-41d4-a716-446655440001',
                        'name' => 'Ville de Lyon',
                        'code' => '69123',
                        'codeType' => 'INSEE',
                        'departmentName' => 'Rhône',
                        'departmentCode' => '69',
                    ],
                ],
            ],
        ], $result);
    }
}
