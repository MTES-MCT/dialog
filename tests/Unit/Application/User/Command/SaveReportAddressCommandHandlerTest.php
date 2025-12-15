<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Application\User\Command\SaveReportAddressCommand;
use App\Application\User\Command\SaveReportAddressCommandHandler;
use App\Domain\User\Organization;
use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Adapter\IgnReportClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SaveReportAddressCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $reportAddressRepository;
    private MockObject $dateUtils;
    private MockObject $ignReportClient;
    private MockObject $organizationRepository;
    private MockObject $roadGeocoder;
    private MockObject $logger;
    private MockObject $user;
    private MockObject $response;
    private MockObject $organization;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->reportAddressRepository = $this->createMock(ReportAddressRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->ignReportClient = $this->createMock(IgnReportClient::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->user = $this->createMock(User::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->organization = $this->createMock(Organization::class);
    }

    public function testHandle(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::any())
            ->method('getUuid')
            ->willReturn('user-uuid');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $reportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Route départementale - D12',
            user: $this->user,
        );
        $reportAddress->setCreatedAt($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($reportAddress));

        $this->organization
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn('{"type":"Polygon","coordinates":[[[2.0,46.0],[3.0,46.0],[3.0,47.0],[2.0,47.0],[2.0,46.0]]]}');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($this->organization);

        $this->organizationRepository
            ->expects(self::once())
            ->method('computeCentroidFromGeoJson')
            ->with('{"type":"Polygon","coordinates":[[[2.0,46.0],[3.0,46.0],[3.0,47.0],[2.0,47.0],[2.0,46.0]]]}')
            ->willReturn('{"type":"Point","coordinates":[2.5,46.5]}');

        $this->ignReportClient
            ->expects(self::once())
            ->method('submitReport')
            ->with(
                'Il y a un problème avec cette adresse.',
                'POINT(2.5 46.5)',
            )
            ->willReturn($this->response);

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
        );
        $handler($command);
    }

    public function testHandleWithRoadBanId(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, 'road-ban-id-123', 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Ville (75001) - Rue de la Paix';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::any())
            ->method('getUuid')
            ->willReturn('user-uuid');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->isInstanceOf(ReportAddress::class));

        $geoJson = '{"type":"LineString","coordinates":[[2.0,46.0],[3.0,47.0]]}';
        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('road-ban-id-123')
            ->willReturn($geoJson);

        $this->organizationRepository
            ->expects(self::once())
            ->method('computeCentroidFromGeoJson')
            ->with($geoJson)
            ->willReturn('{"type":"Point","coordinates":[2.5,46.5]}');

        $this->ignReportClient
            ->expects(self::once())
            ->method('submitReport')
            ->with(
                'Il y a un problème avec cette adresse.',
                'POINT(2.5 46.5)',
            )
            ->willReturn($this->response);

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
        );
        $handler($command);
    }

    public function testHandleWithRoadBanIdFailure(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, 'road-ban-id-123', 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Ville (75001) - Rue de la Paix';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::any())
            ->method('getUuid')
            ->willReturn('user-uuid');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->isInstanceOf(ReportAddress::class));

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('road-ban-id-123')
            ->willThrowException(new GeocodingFailureException("no result found for roadBanId='road-ban-id-123'"));

        $this->organization
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn('{"type":"Polygon","coordinates":[[[2.0,46.0],[3.0,46.0],[3.0,47.0],[2.0,47.0],[2.0,46.0]]]}');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($this->organization);

        $this->organizationRepository
            ->expects(self::once())
            ->method('computeCentroidFromGeoJson')
            ->with('{"type":"Polygon","coordinates":[[[2.0,46.0],[3.0,46.0],[3.0,47.0],[2.0,47.0],[2.0,46.0]]]}')
            ->willReturn('{"type":"Point","coordinates":[2.5,46.5]}');

        $this->ignReportClient
            ->expects(self::once())
            ->method('submitReport')
            ->with(
                'Il y a un problème avec cette adresse.',
                'POINT(2.5 46.5)',
            )
            ->willReturn($this->response);

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
        );
        $handler($command);
    }

    public function testHandleWithRoadBanIdAndOrganizationGeometryBothFail(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, 'road-ban-id-123', 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Ville (75001) - Rue de la Paix';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::any())
            ->method('getUuid')
            ->willReturn('user-uuid');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->isInstanceOf(ReportAddress::class));

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('road-ban-id-123')
            ->willThrowException(new GeocodingFailureException("no result found for roadBanId='road-ban-id-123'"));

        // Organization has no geometry
        $this->organization
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn(null);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($this->organization);

        // No call to computeCentroidFromGeoJson since both geometries failed
        $this->organizationRepository
            ->expects(self::never())
            ->method('computeCentroidFromGeoJson');

        // No call to IGN API
        $this->ignReportClient
            ->expects(self::never())
            ->method('submitReport');

        // Enriched warning log with context
        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Cannot send report to IGN API: geometry not found',
                $this->callback(function (array $context) {
                    return $context['userId'] === 'user-uuid'
                        && $context['organizationUuid'] === 'org-uuid'
                        && $context['roadBanId'] === 'road-ban-id-123'
                        && $context['reason'] === 'roadBanId geocoding failed and organization geometry not found or invalid';
                }),
            );

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
        );
        $handler($command);
    }
}
