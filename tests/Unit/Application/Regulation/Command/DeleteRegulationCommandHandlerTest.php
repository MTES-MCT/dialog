<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command;

use App\Application\Regulation\Command\DeleteRegulationCommandHandler;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\Regulation\Specification\CanDeleteRegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class DeleteRegulationCommandHandlerTest extends TestCase
{
    private $organization;
    private $canDeleteRegulationOrderRecord;
    private $regulationOrderRepository;
    private $regulationOrderRecord;
    private $regulationOrder;

    protected function setUp(): void {
        $this->organization = $this->createMock(Organization::class);
        $this->canDeleteRegulationOrderRecord = $this->createMock(CanDeleteRegulationOrderRecord::class);
        $this->regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->regulationOrder = $this->createMock(RegulationOrder::class);
    }

    public function testDelete(): void
    {
        $organization = $this->createMock(Organization::class);

        $this->canDeleteRegulationOrderRecord
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->organization, $this->regulationOrderRecord)
            ->willReturn(true);

        $this->regulationOrderRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->regulationOrder));

        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);

        $handler = new DeleteRegulationCommandHandler(
            $this->regulationOrderRepository,
            $this->canDeleteRegulationOrderRecord,
        );

        $command = new DeleteRegulationCommand($organization, $this->regulationOrderRecord);
        $this->assertEmpty($handler($command));
    }

    public function testCannotDelete(): void
    {
        $this->expectException(RegulationOrderRecordCannotBeDeletedException::class);

        $organization = $this->createMock(Organization::class);

        $this->canDeleteRegulationOrderRecord
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->organization, $this->regulationOrderRecord)
            ->willReturn(false);

        $this->regulationOrderRepository
            ->expects(self::never())
            ->method('delete');

        $this->regulationOrderRecord
            ->expects(self::never())
            ->method('getRegulationOrder');

        $handler = new DeleteRegulationCommandHandler(
            $this->regulationOrderRepository,
            $this->canDeleteRegulationOrderRecord,
        );

        $command = new DeleteRegulationCommand($organization, $this->regulationOrderRecord);
        $handler($command);
    }
}
