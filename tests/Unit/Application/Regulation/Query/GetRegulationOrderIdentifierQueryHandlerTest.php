<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\Query\GetOrganizationIdentifiersQueryHandler;
use App\Application\Regulation\Query\GetRegulationOrderIdentifierQuery;
use App\Application\Regulation\Query\GetRegulationOrderIdentifierQueryHandler;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderIdentifierQueryHandlerTest extends TestCase
{
    private $regulationOrderRecordRepository;
    private $dateUtils;
    private $organizationRepository;
    private $regulationOrderRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
    }

    public function testGetRegulationOrderIdentifier(): void
    {
        $organizationUuid = 'e0d93630-acf7-4722-81e8-ff7d5fa64b66';
        $organization = new Organization($organizationUuid);
        $mockDate = new \DateTimeImmutable('2022-01-20 08:41:57');

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('countRegulationOrderRecordsForOrganizationDuringCurrentMonth')
            ->with($organizationUuid)
            ->willReturn(11)
        ;

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('getNow')
            ->willReturn($mockDate)
        ;

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($organizationUuid)
            ->willReturn($organization)
        ;

        $this->regulationOrderRepository
            ->expects(self::once())
            ->method('findIdentifiersByOrganization')
            ->with($organization)
            ->willReturn(['2022-01-0011'])
        ;

        $handler = new GetRegulationOrderIdentifierQueryHandler(
            $this->regulationOrderRecordRepository,
            $this->dateUtils,
            $this->organizationRepository,
            new GetOrganizationIdentifiersQueryHandler($this->regulationOrderRepository),
        );

        $result = $handler(new GetRegulationOrderIdentifierQuery($organizationUuid));

        $this->assertEquals('2022-01-0012', $result);
    }

    public function testGetRegulationOrderIdentifierLoopsUntilIdentifierIsUnique(): void
    {
        $organizationUuid = 'f0f7831f-a76b-4f26-bf7c-0de22f028a08';
        $organization = new Organization($organizationUuid);
        $mockDate = new \DateTimeImmutable('2022-02-02 10:00:00');

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('countRegulationOrderRecordsForOrganizationDuringCurrentMonth')
            ->with($organizationUuid)
            ->willReturn(5)
        ;

        $this->dateUtils
            ->expects(self::exactly(3))
            ->method('getNow')
            ->willReturn($mockDate)
        ;

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($organizationUuid)
            ->willReturn($organization)
        ;

        $this->regulationOrderRepository
            ->expects(self::once())
            ->method('findIdentifiersByOrganization')
            ->with($organization)
            ->willReturn(['2022-02-0005', '2022-02-0006'])
        ;

        $handler = new GetRegulationOrderIdentifierQueryHandler(
            $this->regulationOrderRecordRepository,
            $this->dateUtils,
            $this->organizationRepository,
            new GetOrganizationIdentifiersQueryHandler($this->regulationOrderRepository),
        );

        $result = $handler(new GetRegulationOrderIdentifierQuery($organizationUuid));

        $this->assertEquals('2022-02-0007', $result);
    }

    public function testGetRegulationOrderIdentifierThrowsWhenOrganizationMissing(): void
    {
        $organizationUuid = 'b1a5d8f0-0cdd-4cfa-82fb-a48a880a7a8c';

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('countRegulationOrderRecordsForOrganizationDuringCurrentMonth')
            ->with($organizationUuid)
            ->willReturn(0)
        ;

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn(new \DateTimeImmutable('2022-01-01 00:00:00'))
        ;

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($organizationUuid)
            ->willReturn(null)
        ;

        $this->regulationOrderRepository
            ->expects(self::never())
            ->method('findIdentifiersByOrganization')
        ;

        $handler = new GetRegulationOrderIdentifierQueryHandler(
            $this->regulationOrderRecordRepository,
            $this->dateUtils,
            $this->organizationRepository,
            new GetOrganizationIdentifiersQueryHandler($this->regulationOrderRepository),
        );

        $this->expectException(OrganizationNotFoundException::class);
        $handler(new GetRegulationOrderIdentifierQuery($organizationUuid));
    }
}
