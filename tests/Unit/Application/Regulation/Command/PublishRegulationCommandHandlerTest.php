<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\PublishRegulationCommandHandler;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class PublishRegulationCommandHandlerTest extends TestCase
{
    private $regulationOrderRecordRepository;

    protected function setUp(): void
    {
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $this->expectException(RegulationOrderRecordNotFoundException::class);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('df4454e1-64e8-46ff-a6c1-7f9c35375802')
            ->willReturn(null);

        $handler = new PublishRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
        );

        $command = new PublishRegulationCommand('df4454e1-64e8-46ff-a6c1-7f9c35375802', 'draft');
        $handler($command);
    }

    public function testSaveDraft(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateStatus')
            ->with('draft');

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('df4454e1-64e8-46ff-a6c1-7f9c35375802')
            ->willReturn($regulationOrderRecord);

        $handler = new PublishRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
        );

        $command = new PublishRegulationCommand('df4454e1-64e8-46ff-a6c1-7f9c35375802', 'draft');
        $this->assertEmpty($handler($command));
    }

    public function testSavePublished(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateStatus')
            ->with('published');

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('df4454e1-64e8-46ff-a6c1-7f9c35375802')
            ->willReturn($regulationOrderRecord);

        $handler = new PublishRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
        );

        $command = new PublishRegulationCommand('df4454e1-64e8-46ff-a6c1-7f9c35375802', 'published');
        $this->assertEmpty($handler($command));
    }
}
