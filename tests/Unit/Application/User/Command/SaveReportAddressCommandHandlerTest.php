<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\Exception\EmailSendingException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\IdFactoryInterface;
use App\Application\MailerInterface;
use App\Application\RoadGeocoderInterface;
use App\Application\User\Command\SaveReportAddressCommand;
use App\Application\User\Command\SaveReportAddressCommandHandler;
use App\Domain\Mail;
use App\Domain\User\Organization;
use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class SaveReportAddressCommandHandlerTest extends TestCase
{
    private MockObject&IdFactoryInterface $idFactory;
    private MockObject&ReportAddressRepositoryInterface $reportAddressRepository;
    private MockObject&DateUtilsInterface $dateUtils;
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
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->user = $this->createMock(User::class);
        $this->organization = $this->createMock(Organization::class);
    }

    private function createHandler(): SaveReportAddressCommandHandler
    {
        return new SaveReportAddressCommandHandler(
            $this->idFactory,
            $this->reportAddressRepository,
            $this->dateUtils,
            $this->organizationRepository,
            $this->roadGeocoder,
            $this->logger,
            $this->mailer,
            'support@example.com',
        );
    }

    private function expectEmailSent(): void
    {
        $this->user->method('getFullName')->willReturn('Jean Dupont');
        $this->user->method('getEmail')->willReturn('jean@example.com');
    }

    public function testHandleComputesGeometryFromOrganization(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

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

        $expectedReportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Route départementale - D12',
            user: $this->user,
        );
        $expectedReportAddress->setCreatedAt($date);
        $expectedReportAddress->setIgnGeometry('POINT(2.5 46.5)');

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($expectedReportAddress));

        $this->expectEmailSent();
        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with($this->equalTo(new Mail(
                address: 'support@example.com',
                subject: 'contact.email.user_report_subject',
                template: 'email/user/user_report.html.twig',
                payload: [
                    'content' => 'Il y a un problème avec cette adresse.',
                    'location' => 'Route départementale - D12',
                    'fullName' => 'Jean Dupont',
                    'contactEmail' => 'jean@example.com',
                ],
            )));

        $handler = $this->createHandler();
        $handler($command);
    }

    public function testHandleComputesGeometryFromRoadBanId(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, 'road-ban-id-123', 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Ville (75001) - Rue de la Paix';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

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

        $expectedReportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Ville (75001) - Rue de la Paix',
            user: $this->user,
        );
        $expectedReportAddress->setCreatedAt($date);
        $expectedReportAddress->setIgnGeometry('POINT(2.5 46.5)');

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($expectedReportAddress));

        $this->expectEmailSent();
        $this->mailer->expects(self::once())->method('send');

        $handler = $this->createHandler();
        $handler($command);
    }

    public function testHandleWithRoadBanIdFailureFallsBackToOrganization(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, 'road-ban-id-123', 'org-uuid');
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Ville (75001) - Rue de la Paix';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->idFactory->expects(self::once())->method('make')->willReturn('0de5692b-cab1-494c-804d-765dc14df674');
        $this->dateUtils->expects(self::once())->method('getNow')->willReturn($date);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('road-ban-id-123')
            ->willThrowException(new GeocodingFailureException('geocoding failed'));

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

        $expectedReportAddress = new ReportAddress(
            uuid: '0de5692b-cab1-494c-804d-765dc14df674',
            content: 'Il y a un problème avec cette adresse.',
            location: 'Ville (75001) - Rue de la Paix',
            user: $this->user,
        );
        $expectedReportAddress->setCreatedAt($date);
        $expectedReportAddress->setIgnGeometry('POINT(2.5 46.5)');

        $this->reportAddressRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($expectedReportAddress));

        $this->expectEmailSent();
        $this->mailer->expects(self::once())->method('send');

        $handler = $this->createHandler();
        $handler($command);
    }

    public function testHandleWithoutGeometryStoresNull(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, null);
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user->expects(self::once())->method('getUuid')->willReturn('user-uuid');
        $this->idFactory->expects(self::once())->method('make')->willReturn('0de5692b-cab1-494c-804d-765dc14df674');
        $this->dateUtils->expects(self::once())->method('getNow')->willReturn($date);

        $this->organizationRepository->expects(self::never())->method('findOneByUuid');

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with('Cannot compute report geometry for IGN submission');

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

        $this->expectEmailSent();
        $this->mailer->expects(self::once())->method('send');

        $handler = $this->createHandler();
        $handler($command);
    }

    public function testHandleWhenEmailSendingFails(): void
    {
        $command = new SaveReportAddressCommand($this->user, null, null, null, null, null, null);
        $command->content = 'Il y a un problème avec cette adresse.';
        $command->location = 'Route départementale - D12';
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');

        $this->user->method('getUuid')->willReturn('user-uuid');
        $this->idFactory->expects(self::once())->method('make')->willReturn('0de5692b-cab1-494c-804d-765dc14df674');
        $this->dateUtils->expects(self::once())->method('getNow')->willReturn($date);

        $this->reportAddressRepository->expects(self::once())->method('add');

        $this->expectEmailSent();
        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new \Exception('Email service unavailable'));

        $handler = $this->createHandler();

        $this->expectException(EmailSendingException::class);
        $this->expectExceptionMessage('Failed to send feedback by email : Email service unavailable');

        $handler($command);
    }
}
