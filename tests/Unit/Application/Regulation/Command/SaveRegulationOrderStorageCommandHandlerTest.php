<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\SaveRegulationOrderStorageCommand;
use App\Application\Regulation\Command\SaveRegulationOrderStorageCommandHandler;
use App\Application\StorageInterface;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;
use App\Domain\Regulation\StorageRegulationOrder;
use App\Infrastructure\Adapter\IdFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SaveRegulationOrderStorageCommandHandlerTest extends TestCase
{
    private $storage;
    private $idFactory;
    private $storageRegulationOrderRepository;

    public function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->idFactory = $this->createMock(IdFactory::class);
        $this->storageRegulationOrderRepository = $this->createMock(StorageRegulationOrderRepositoryInterface::class);
    }

    public function testSave(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $storageRegulationOrder = $this->createMock(StorageRegulationOrder::class);

        $regulationOrder
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('496bd752-c217-4625-ba0c-7454dc218516');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn(
                'd035fec0-30f3-4134-95b9-d74c68eb53e3',
            );

        $this->storage
            ->expects(self::once())
            ->method('write')
            ->with('regulationOrder/496bd752-c217-4625-ba0c-7454dc218516', $file)
            ->willReturn('regulationOrder/496bd752-c217-4625-ba0c-7454dc218516/storageRegulationOrder.pdf');

        $this->storageRegulationOrderRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new StorageRegulationOrder(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        regulationOrder: $regulationOrder,
                        path: 'regulationOrder/496bd752-c217-4625-ba0c-7454dc218516/storageRegulationOrder.pdf',
                        url: 'https://www.herault.gouv.fr/content/download/21272/158268/file/arrete_circulation_vtm.pdf',
                        title: 'Titre test',
                    ),
                ),
            );

        $handler = new SaveRegulationOrderStorageCommandHandler(
            $this->storage,
            $this->storageRegulationOrderRepository,
            $this->idFactory,
        );

        $command = new SaveRegulationOrderStorageCommand($regulationOrder, null);
        $command->file = $file;
        $command->url = 'https://www.herault.gouv.fr/content/download/21272/158268/file/arrete_circulation_vtm.pdf';
        $command->title = 'Titre test';

        $handler($command);
    }
}
