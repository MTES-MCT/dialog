<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command;

use App\Application\Regulation\Command\DeleteRegulationCommandHandler;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Specification\CanUserDeleteRegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class DeleteRegulationCommandHandlerTest extends TestCase
{
    private $uuid = 'f331d768-ed8b-496d-81ce-b97008f338d0';
    private $canUserDeleteRegulationOrderRecord;
    private $regulationOrderRecordRepository;
    private $regulationOrderRecord;

    protected function setUp(): void {
        $this->canUserDeleteRegulationOrderRecord = $this->createMock(CanUserDeleteRegulationOrderRecord::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
    }

    public function testDelete(): void
    {
        $this->canUserDeleteRegulationOrderRecord
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->willReturn(true);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($this->uuid)
            ->willReturn($this->regulationOrderRecord);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->regulationOrderRecord));

        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getStatus')
            ->willReturn('published');

        $handler = new DeleteRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
            $this->canUserDeleteRegulationOrderRecord,
        );

        $command = new DeleteRegulationCommand($this->uuid, ['...']);
        $this->assertSame('published', $handler($command));
    }

    public function testUserCannotDelete(): void
    {
        $this->expectException(RegulationOrderRecordCannotBeDeletedException::class);

        $this->canUserDeleteRegulationOrderRecord
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->willReturn(false);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($this->uuid)
            ->willReturn($this->regulationOrderRecord);

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('delete');

        $this->regulationOrderRecord
            ->expects(self::never())
            ->method('getStatus');

        $handler = new DeleteRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
            $this->canUserDeleteRegulationOrderRecord,
        );

        $command = new DeleteRegulationCommand($this->uuid, ['...']);
        $handler($command);
    }

    public function testNotFound(): void
    {
        $this->expectException(RegulationOrderRecordNotFoundException::class);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($this->uuid)
            ->willReturn(null);

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('delete');

        $handler = new DeleteRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
            $this->canUserDeleteRegulationOrderRecord,
        );

        $command = new DeleteRegulationCommand($this->uuid, ['...']);
        $handler($command);
    }
}
