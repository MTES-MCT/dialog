<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command;

use App\Application\Regulation\Command\DeleteRegulationCommandHandler;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteRegulationCommandHandlerTest extends TestCase
{
    public function testDelete(): void
    {
        $uuid = 'f331d768-ed8b-496d-81ce-b97008f338d0';
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $regulationOrderRecord
            ->expects(self::once())
            ->method('getStatus')
            ->willReturn('published');

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($uuid)
            ->willReturn($regulationOrderRecord);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($regulationOrderRecord));

        $handler = new DeleteRegulationCommandHandler(
            $regulationOrderRecordRepository,
        );

        $command = new DeleteRegulationCommand($uuid);
        $this->assertSame('published', $handler($command));
    }

    public function testNotFound(): void
    {
        $this->expectException(RegulationOrderRecordNotFoundException::class);

        $uuid = 'f331d768-ed8b-496d-81ce-b97008f338d0';
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($uuid)
            ->willReturn(null);

        $regulationOrderRecordRepository
            ->expects(self::never())
            ->method('delete');

        $handler = new DeleteRegulationCommandHandler(
            $regulationOrderRecordRepository,
        );

        $command = new DeleteRegulationCommand($uuid);
        $handler($command);
    }
}
