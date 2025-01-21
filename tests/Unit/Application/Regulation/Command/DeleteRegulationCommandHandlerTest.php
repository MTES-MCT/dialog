<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Application\Regulation\Command\DeleteRegulationCommandHandler;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\User\Organization;
use App\Domain\User\User;
use App\Infrastructure\Security\AuthenticatedUser;
use PHPUnit\Framework\TestCase;

final class DeleteRegulationCommandHandlerTest extends TestCase
{
    private $organizationUuids;
    private $canOrganizationAccessToRegulation;
    private $regulationOrderRepository;
    private $regulationOrderRecord;
    private $regulationOrder;
    private $commandBus;
    private $authenticatedUser;

    protected function setUp(): void
    {
        $this->organizationUuids = ['c3d4444e-5e45-4134-ad22-32f1a72b8214'];
        $this->canOrganizationAccessToRegulation = $this->createMock(CanOrganizationAccessToRegulation::class);
        $this->regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->regulationOrder = $this->createMock(RegulationOrder::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->authenticatedUser = $this->createMock(AuthenticatedUser::class);
    }

    public function testDelete(): void
    {
        $organization = $this->createMock(Organization::class);
        $user = $this->createMock(User::class);

        $this->canOrganizationAccessToRegulation
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->regulationOrderRecord, $this->organizationUuids)
            ->willReturn(true);

        $this->regulationOrderRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->regulationOrder));

        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);

        $this->authenticatedUser
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $handler = new DeleteRegulationCommandHandler(
            $this->regulationOrderRepository,
            $this->canOrganizationAccessToRegulation, $this->commandBus,
            $this->authenticatedUser,
        );

        $command = new DeleteRegulationCommand($this->organizationUuids, $this->regulationOrderRecord);
        $this->assertEmpty($handler($command));
    }

    public function testCannotDelete(): void
    {
        $this->expectException(RegulationOrderRecordCannotBeDeletedException::class);

        $organization = $this->createMock(Organization::class);

        $this->canOrganizationAccessToRegulation
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->regulationOrderRecord, $this->organizationUuids)
            ->willReturn(false);

        $this->regulationOrderRepository
            ->expects(self::never())
            ->method('delete');

        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder');

        $handler = new DeleteRegulationCommandHandler(
            $this->regulationOrderRepository,
            $this->canOrganizationAccessToRegulation,
            $this->commandBus,
            $this->authenticatedUser,
        );

        $command = new DeleteRegulationCommand($this->organizationUuids, $this->regulationOrderRecord);
        $handler($command);
    }
}
