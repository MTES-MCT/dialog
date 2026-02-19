<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\Exception\EmailSendingException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\IdFactoryInterface;
use App\Application\Ign\IgnReportSubmissionResult;
use App\Application\MailerInterface;
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

final class SaveReportAddressCommandHandlerTest extends TestCase
{
    private MockObject&IdFactoryInterface $idFactory;
    private MockObject&ReportAddressRepositoryInterface $reportAddressRepository;
    private MockObject&DateUtilsInterface $dateUtils;
    private MockObject&IgnReportClient $ignReportClient;
    private MockObject&OrganizationRepositoryInterface $organizationRepository;
    private MockObject&RoadGeocoderInterface $roadGeocoder;
    private MockObject&LoggerInterface $logger;
    private MockObject&MailerInterface $mailer;
    private MockObject&User $user;
    private MockObject&Organization $organization;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->reportAddressRepository = $this->createMock(ReportAddressRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->ignReportClient = $this->createMock(IgnReportClient::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->user = $this->createMock(User::class);
        $this->organization = $this->createMock(Organization::class);
    }

    public function testHandle(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::once())
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
            ->willReturn(new IgnReportSubmissionResult('42', 'submit'));

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
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
            ->expects(self::once())
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

        $expectedReportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Ville (75001) - Rue de la Paix',
            user: $this->user,
        );
        $expectedReportAddress->setCreatedAt($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($expectedReportAddress));

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
            ->willReturn(new IgnReportSubmissionResult('99', 'submit'));

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
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
            ->expects(self::once())
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

        $expectedReportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Ville (75001) - Rue de la Paix',
            user: $this->user,
        );
        $expectedReportAddress->setCreatedAt($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($expectedReportAddress));

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
            ->willReturn(new IgnReportSubmissionResult('42', 'submit'));

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
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
            ->expects(self::once())
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

        $expectedReportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Ville (75001) - Rue de la Paix',
            user: $this->user,
        );
        $expectedReportAddress->setCreatedAt($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($expectedReportAddress));

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('road-ban-id-123')
            ->willThrowException(new GeocodingFailureException("no result found for roadBanId='road-ban-id-123'"));

        $this->organization
            ->expects(self::once())
            ->method('getGeometry')
            ->willReturn(null);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($this->organization);

        $this->organizationRepository
            ->expects(self::never())
            ->method('computeCentroidFromGeoJson');

        $this->ignReportClient
            ->expects(self::never())
            ->method('submitReport');

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
            $this->mailer,
            'support@example.com',
        );
        $handler($command);
    }

    public function testHandleWhenIgnApiCallFails(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::once())
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

        $expectedReportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Route départementale - D12',
            user: $this->user,
        );
        $expectedReportAddress->setCreatedAt($date);

        // Le ReportAddress doit être sauvegardé même si l'appel IGN échoue
        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($expectedReportAddress));

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

        // L'appel IGN lève une exception
        $this->ignReportClient
            ->expects(self::once())
            ->method('submitReport')
            ->with(
                'Il y a un problème avec cette adresse.',
                'POINT(2.5 46.5)',
            )
            ->willThrowException(new \Exception('IGN API error: service unavailable'));

        // L'exception doit être catchée et loggée
        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send report to IGN API',
                [
                    'userId' => 'user-uuid',
                    'error' => 'IGN API error: service unavailable',
                ],
            );

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
        );

        $handler($command);
    }

    public function testHandleWithInvalidGeoJsonReturnsNull(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::once())
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

        $expectedReportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Route départementale - D12',
            user: $this->user,
        );
        $expectedReportAddress->setCreatedAt($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($expectedReportAddress));

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
            ->willReturn('');

        $this->ignReportClient
            ->expects(self::never())
            ->method('submitReport');

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Cannot send report to IGN API: geometry not found',
                $this->callback(function (array $context) {
                    return $context['userId'] === 'user-uuid'
                        && $context['organizationUuid'] === 'org-uuid'
                        && $context['reason'] === 'no roadBanId provided and organization geometry not found or invalid';
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
            $this->mailer,
            'support@example.com',
        );

        $handler($command);
    }

    public function testHandleWithMalformedGeoJsonReturnsNull(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::once())
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

        $this->organization
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn('{"type":"Polygon","coordinates":[[[2.0,46.0]]]}');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($this->organization);

        // computeCentroidFromGeoJson retourne un JSON malformé
        $this->organizationRepository
            ->expects(self::once())
            ->method('computeCentroidFromGeoJson')
            ->willReturn('not a valid json');

        $this->ignReportClient
            ->expects(self::never())
            ->method('submitReport');

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with('Cannot send report to IGN API: geometry not found', $this->anything());

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
        );

        $handler($command);
    }

    public function testHandleWithGeoJsonMissingCoordinates(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::once())
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

        $this->organization
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn('{"type":"Polygon","coordinates":[[[2.0,46.0]]]}');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($this->organization);

        // GeoJSON sans coordonnées
        $this->organizationRepository
            ->expects(self::once())
            ->method('computeCentroidFromGeoJson')
            ->willReturn('{"type":"Point"}');

        $this->ignReportClient
            ->expects(self::never())
            ->method('submitReport');

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with('Cannot send report to IGN API: geometry not found', $this->anything());

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
        );

        $handler($command);
    }

    public function testHandleWithGeoJsonInvalidCoordinatesArray(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::once())
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

        $this->organization
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn('{"type":"Polygon","coordinates":[[[2.0,46.0]]]}');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($this->organization);

        // GeoJSON avec seulement 1 coordonnée
        $this->organizationRepository
            ->expects(self::once())
            ->method('computeCentroidFromGeoJson')
            ->willReturn('{"type":"Point","coordinates":[2.5]}');

        $this->ignReportClient
            ->expects(self::never())
            ->method('submitReport');

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with('Cannot send report to IGN API: geometry not found', $this->anything());

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
        );

        $handler($command);
    }

    public function testHandleWithGeoJsonNullCoordinates(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::once())
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

        $this->organization
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn('{"type":"Polygon","coordinates":[[[2.0,46.0]]]}');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($this->organization);

        // GeoJSON avec coordonnées null
        $this->organizationRepository
            ->expects(self::once())
            ->method('computeCentroidFromGeoJson')
            ->willReturn('{"type":"Point","coordinates":[null,null]}');

        $this->ignReportClient
            ->expects(self::never())
            ->method('submitReport');

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with('Cannot send report to IGN API: geometry not found', $this->anything());

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
        );

        $handler($command);
    }

    public function testHandleWithGeoJsonEmptyStringCoordinates(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::once())
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

        $this->organization
            ->expects(self::exactly(2))
            ->method('getGeometry')
            ->willReturn('{"type":"Polygon","coordinates":[[[2.0,46.0]]]}');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($this->organization);

        // GeoJSON avec coordonnées vides
        $this->organizationRepository
            ->expects(self::once())
            ->method('computeCentroidFromGeoJson')
            ->willReturn('{"type":"Point","coordinates":["",""]}');

        $this->ignReportClient
            ->expects(self::never())
            ->method('submitReport');

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with('Cannot send report to IGN API: geometry not found', $this->anything());

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
        );

        $handler($command);
    }

    public function testHandleWhenEmailSendingFails(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('user-uuid');

        $this->user
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Jean Dupont');

        $this->user
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn('jean@example.com');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $expectedReportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Route départementale - D12',
            user: $this->user,
        );
        $expectedReportAddress->setCreatedAt($date);

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($expectedReportAddress));

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
            ->willReturn(new IgnReportSubmissionResult('42', 'submit'));

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new \Exception('Email service unavailable'));

        $handler = new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->ignReportClient,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
        );

        $this->expectException(EmailSendingException::class);
        $this->expectExceptionMessage('Failed to send feedback by email : Email service unavailable');

        $handler($command);
    }
}
