<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command\Steps;

use App\Application\Regulation\Command\Steps\SaveRegulationStep5Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep5CommandHandler;
use App\Domain\Regulation\Exception\RegulationOrderCannotBePublishedException;
use App\Domain\Regulation\Exception\RegulationOrderNotFoundException;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\Regulation\Specification\CanRegulationOrderBePublished;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep5CommandHandlerTest extends TestCase
{
    private $canRegulationOrderBePublished;
    private $regulationOrderRepository;

    protected function setUp(): void
    {
        $this->canRegulationOrderBePublished = $this->createMock(CanRegulationOrderBePublished::class);
        $this->regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $this->expectException(RegulationOrderNotFoundException::class);

        $this->regulationOrderRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('df4454e1-64e8-46ff-a6c1-7f9c35375802')
            ->willReturn(null);

        $this->canRegulationOrderBePublished
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $handler = new SaveRegulationStep5CommandHandler(
            $this->regulationOrderRepository,
            $this->canRegulationOrderBePublished,
        );

        $command = new SaveRegulationStep5Command('df4454e1-64e8-46ff-a6c1-7f9c35375802', 'draft');
        $handler($command);
    }

    public function testSaveDraft(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateStatus')
            ->with('draft');

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);

        $this->regulationOrderRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('df4454e1-64e8-46ff-a6c1-7f9c35375802')
            ->willReturn($regulationOrder);

        $this->canRegulationOrderBePublished
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $handler = new SaveRegulationStep5CommandHandler(
            $this->regulationOrderRepository,
            $this->canRegulationOrderBePublished,
        );

        $command = new SaveRegulationStep5Command('df4454e1-64e8-46ff-a6c1-7f9c35375802', 'draft');
        $this->assertEmpty($handler($command));
    }

    public function testSavePublished(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateStatus')
            ->with('published');

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);

        $this->regulationOrderRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('df4454e1-64e8-46ff-a6c1-7f9c35375802')
            ->willReturn($regulationOrder);

        $this->canRegulationOrderBePublished
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($regulationOrder)
            ->willReturn(true);

        $handler = new SaveRegulationStep5CommandHandler(
            $this->regulationOrderRepository,
            $this->canRegulationOrderBePublished,
        );

        $command = new SaveRegulationStep5Command('df4454e1-64e8-46ff-a6c1-7f9c35375802', 'published');
        $this->assertEmpty($handler($command));
    }

    public function testRegulationCannotBePublished(): void
    {
        $this->expectException(RegulationOrderCannotBePublishedException::class);

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::never())
            ->method('getRegulationOrderRecord');

        $this->regulationOrderRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('df4454e1-64e8-46ff-a6c1-7f9c35375802')
            ->willReturn($regulationOrder);

        $this->canRegulationOrderBePublished
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($regulationOrder)
            ->willReturn(false);

        $handler = new SaveRegulationStep5CommandHandler(
            $this->regulationOrderRepository,
            $this->canRegulationOrderBePublished,
        );

        $command = new SaveRegulationStep5Command('df4454e1-64e8-46ff-a6c1-7f9c35375802', 'published');
        $handler($command);
    }
}
