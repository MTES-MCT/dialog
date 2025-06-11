<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\DeleteRegulationOrderStorageCommand;
use App\Application\Regulation\Command\DeleteRegulationOrderStorageCommandHandler;
use App\Application\StorageInterface;
use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;
use App\Domain\Regulation\StorageRegulationOrder;
use PHPUnit\Framework\TestCase;

final class DeleteRegulationOrderStorageCommandHandlerTest extends TestCase
{
    private $storageRegulationOrderRepository;
    private $storage;
    private $storageRegulationOrder;

    protected function setUp(): void
    {
        $this->storageRegulationOrderRepository = $this->createMock(StorageRegulationOrderRepositoryInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->storageRegulationOrder = $this->createMock(StorageRegulationOrder::class);
    }

    public function testDelete(): void
    {
        $this->storageRegulationOrder
            ->expects(self::once())
            ->method('getPath')
            ->willReturn('regulationOrder/496bd752-c217-4625-ba0c-7454dc218516/storageRegulationOrder.pdf');

        $this->storage
            ->expects(self::once())
            ->method('delete')
            ->with('regulationOrder/496bd752-c217-4625-ba0c-7454dc218516/storageRegulationOrder.pdf');

        $this->storageRegulationOrder
            ->expects(self::once())
            ->method('setPath')
            ->with(null);

        $this->storageRegulationOrderRepository
            ->expects(self::once())
            ->method('remove')
            ->with($this->storageRegulationOrder);

        $handler = new DeleteRegulationOrderStorageCommandHandler(
            $this->storage,
            $this->storageRegulationOrderRepository,
        );

        $command = new DeleteRegulationOrderStorageCommand($this->storageRegulationOrder);

        $this->assertEmpty($handler($command));
    }
}
