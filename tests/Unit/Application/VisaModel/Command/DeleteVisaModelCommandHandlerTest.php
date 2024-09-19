<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\VisaModel\Command;

use App\Application\VisaModel\Command\DeleteVisaModelCommand;
use App\Application\VisaModel\Command\DeleteVisaModelCommandHandler;
use App\Domain\VisaModel\Exception\VisaModelNotFoundException;
use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;
use App\Domain\VisaModel\VisaModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteVisaModelCommandHandlerTest extends TestCase
{
    private MockObject $visaModelRepository;

    public function setUp(): void
    {
        $this->visaModelRepository = $this->createMock(VisaModelRepositoryInterface::class);
    }

    public function testRemove(): void
    {
        $visaModel = $this->createMock(VisaModel::class);

        $this->visaModelRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn($visaModel);

        $this->visaModelRepository
            ->expects(self::once())
            ->method('remove')
            ->with($visaModel);

        $handler = new DeleteVisaModelCommandHandler(
            $this->visaModelRepository,
        );
        $command = new DeleteVisaModelCommand('f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler($command);
    }

    public function testNotFound(): void
    {
        $this->expectException(VisaModelNotFoundException::class);

        $this->visaModelRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn(null);

        $this->visaModelRepository
            ->expects(self::never())
            ->method('remove');

        $handler = new DeleteVisaModelCommandHandler(
            $this->visaModelRepository,
        );
        $command = new DeleteVisaModelCommand('f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler($command);
    }
}
