<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\Command;

use App\Application\DateUtilsInterface;
use App\Application\Organization\Command\SyncOrganizationAdministrativeBoundariesCommand;
use App\Application\Organization\Command\SyncOrganizationAdministrativeBoundariesCommandHandler;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\Adapter\OrganizationAdministrativeBoundariesGeometry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class SyncOrganizationAdministrativeBoundariesCommandHandlerTest extends TestCase
{
    private MockObject $organizationRepository;
    private MockObject $administrativeBoundariesGeometry;
    private MockObject $dateUtils;
    private MockObject $logger;
    private SyncOrganizationAdministrativeBoundariesCommandHandler $handler;
    private SyncOrganizationAdministrativeBoundariesCommand $command;
    private string $organizationUuid = '32516746-4fce-4750-ba83-7ae9b4290678';
    private \DateTimeImmutable $now;

    public function setUp(): void
    {
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->administrativeBoundariesGeometry = $this->createMock(OrganizationAdministrativeBoundariesGeometry::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new SyncOrganizationAdministrativeBoundariesCommandHandler(
            $this->organizationRepository,
            $this->administrativeBoundariesGeometry,
            $this->dateUtils,
            $this->logger,
        );

        $this->command = new SyncOrganizationAdministrativeBoundariesCommand($this->organizationUuid);
        $this->now = new \DateTimeImmutable('2024-05-07');
    }

    public function testSyncGeometrySuccessfully(): void
    {
        $organization = (new Organization($this->organizationUuid))
            ->setName('Commune de Saint Ouen')
            ->setSiret('82050375300015')
            ->setCode('93070')
            ->setCodeType(OrganizationCodeTypeEnum::INSEE->value);

        $geometry = '{"type":"Polygon","coordinates":[[[2.3,48.9],[2.4,48.9],[2.4,49.0],[2.3,49.0],[2.3,48.9]]]}';

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($this->organizationUuid)
            ->willReturn($organization);

        $this->administrativeBoundariesGeometry
            ->expects(self::once())
            ->method('findByCodes')
            ->with($organization->getCode(), $organization->getCodeType())
            ->willReturn($geometry);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($this->now);

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with('Organization geometry synced', [
                'siret' => $organization->getSiret(),
                'name' => $organization->getName(),
            ]);

        ($this->handler)($this->command);

        $this->assertEquals($geometry, $organization->getGeometry());
        $this->assertEquals($this->now, $organization->getUpdatedAt());
    }

    public function testOrganizationNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($this->organizationUuid)
            ->willReturn(null);

        $this->administrativeBoundariesGeometry
            ->expects(self::never())
            ->method('findByCodes');

        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $this->logger
            ->expects(self::never())
            ->method('info');

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with('Organization not found', [
                'organizationUuid' => $this->organizationUuid,
            ]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        ($this->handler)($this->command);
    }

    public function testImpossibleToGetOrganizationGeometry(): void
    {
        $organization = (new Organization($this->organizationUuid))
            ->setName('Commune de Saint Ouen')
            ->setSiret('82050375300015')
            ->setCode('93070')
            ->setCodeType(OrganizationCodeTypeEnum::INSEE->value);

        $exception = new \LogicException('No administrative boundaries found');

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($this->organizationUuid)
            ->willReturn($organization);

        $this->administrativeBoundariesGeometry
            ->expects(self::once())
            ->method('findByCodes')
            ->with($organization->getCode(), $organization->getCodeType())
            ->willThrowException($exception);

        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Impossible to get organization geometry', [
                'siret' => $organization->getSiret(),
                'name' => $organization->getName(),
                'message' => $exception->getMessage(),
            ]);

        ($this->handler)($this->command);
    }
}
