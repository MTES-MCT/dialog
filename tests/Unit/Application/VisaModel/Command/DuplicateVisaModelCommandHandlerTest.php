<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\VisaModel\Command;

use App\Application\CommandBusInterface;
use App\Application\VisaModel\Command\DuplicateVisaModelCommand;
use App\Application\VisaModel\Command\DuplicateVisaModelCommandHandler;
use App\Application\VisaModel\Command\SaveVisaModelCommand;
use App\Domain\User\Organization;
use App\Domain\VisaModel\Exception\VisaModelNotFoundException;
use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;
use App\Domain\VisaModel\VisaModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DuplicateVisaModelCommandHandlerTest extends TestCase
{
    private MockObject $visaModelRepository;
    private MockObject $commandBus;

    public function setUp(): void
    {
        $this->visaModelRepository = $this->createMock(VisaModelRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testDuplicate(): void
    {
        $organization = $this->createMock(Organization::class);
        $visaModel = $this->createMock(VisaModel::class);
        $visaModel
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Interdiction de circulation');
        $visaModel
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');
        $visaModel
            ->expects(self::once())
            ->method('getVisas')
            ->willReturn(['vu que 1']);

        $this->visaModelRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn($visaModel);

        $visaModelCommand = new SaveVisaModelCommand($organization);
        $visaModelCommand->name = 'Interdiction de circulation (copie)';
        $visaModelCommand->description = 'Description';
        $visaModelCommand->visas = ['vu que 1'];

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo($visaModelCommand));

        $handler = new DuplicateVisaModelCommandHandler(
            $this->visaModelRepository,
            $this->commandBus,
        );
        $command = new DuplicateVisaModelCommand($organization, 'f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler($command);
    }

    public function testNotFound(): void
    {
        $this->expectException(VisaModelNotFoundException::class);

        $organization = $this->createMock(Organization::class);

        $this->visaModelRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn(null);

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $handler = new DuplicateVisaModelCommandHandler(
            $this->visaModelRepository,
            $this->commandBus,
        );
        $command = new DuplicateVisaModelCommand($organization, 'f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler($command);
    }
}
