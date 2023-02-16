<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\PublishRegulationCommandHandler;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;
use PHPUnit\Framework\TestCase;

final class PublishRegulationCommandHandlerTest extends TestCase
{
    private $canRegulationOrderRecordBePublished;
    private $regulationOrderRecordRepository;

    protected function setUp(): void
    {
        $this->canRegulationOrderRecordBePublished = $this->createMock(CanRegulationOrderRecordBePublished::class);
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

        $this->canRegulationOrderRecordBePublished
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $handler = new PublishRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
            $this->canRegulationOrderRecordBePublished,
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

        $this->canRegulationOrderRecordBePublished
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $handler = new PublishRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
            $this->canRegulationOrderRecordBePublished,
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

        $this->canRegulationOrderRecordBePublished
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($regulationOrderRecord)
            ->willReturn(true);

        $handler = new PublishRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
            $this->canRegulationOrderRecordBePublished,
        );

        $command = new PublishRegulationCommand('df4454e1-64e8-46ff-a6c1-7f9c35375802', 'published');
        $this->assertEmpty($handler($command));
    }

    public function testRegulationCantBePublished(): void
    {
        $this->expectException(RegulationOrderRecordCannotBePublishedException::class);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::never())
            ->method('updateStatus');

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('df4454e1-64e8-46ff-a6c1-7f9c35375802')
            ->willReturn($regulationOrderRecord);

        $this->canRegulationOrderRecordBePublished
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($regulationOrderRecord)
            ->willReturn(false);

        $handler = new PublishRegulationCommandHandler(
            $this->regulationOrderRecordRepository,
            $this->canRegulationOrderRecordBePublished,
        );

        $command = new PublishRegulationCommand('df4454e1-64e8-46ff-a6c1-7f9c35375802', 'published');
        $handler($command);
    }
}
