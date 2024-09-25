<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\VisaModel\Command;

use App\Application\IdFactoryInterface;
use App\Application\VisaModel\Command\SaveVisaModelCommand;
use App\Application\VisaModel\Command\SaveVisaModelCommandHandler;
use App\Domain\User\Organization;
use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;
use App\Domain\VisaModel\VisaModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveVisaModelCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $visaModelRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->visaModelRepository = $this->createMock(VisaModelRepositoryInterface::class);
    }

    public function testAdd(): void
    {
        $organization = $this->createMock(Organization::class);
        $visaModel = $this->createMock(VisaModel::class);

        $visaModel = (new VisaModel('f8216679-5a0b-4dd5-9e2b-b382d298c3b4'))
            ->setName('Réglementation de circulation')
            ->setDescription('Limitation de vitesse')
            ->setVisas(['vu 1', 'vu 2'])
            ->setOrganization($organization);

        $this->visaModelRepository
            ->expects(self::once())
            ->method('add')
            ->with($visaModel)
            ->willReturn($visaModel);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler = new SaveVisaModelCommandHandler(
            $this->idFactory,
            $this->visaModelRepository,
        );
        $command = new SaveVisaModelCommand($organization);
        $command->name = 'Réglementation de circulation';
        $command->description = 'Limitation de vitesse';
        $command->visas = ['vu 1', 'vu 2'];

        $handler($command);
    }

    public function testUpdate(): void
    {
        $organization = $this->createMock(Organization::class);
        $visaModel = $this->createMock(VisaModel::class);
        $visaModel
            ->expects(self::once())
            ->method('update')
            ->with('Réglementation de circulation', ['vu 1', 'vu 2'], 'Limitation de vitesse');

        $this->visaModelRepository
            ->expects(self::never())
            ->method('add');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $handler = new SaveVisaModelCommandHandler(
            $this->idFactory,
            $this->visaModelRepository,
        );
        $command = new SaveVisaModelCommand($organization, $visaModel);
        $command->name = 'Réglementation de circulation';
        $command->description = 'Limitation de vitesse';
        $command->visas = ['vu 1', 'vu 2'];

        $handler($command);
    }
}
