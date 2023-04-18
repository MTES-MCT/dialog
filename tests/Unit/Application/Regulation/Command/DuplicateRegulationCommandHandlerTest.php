<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DuplicateRegulationCommand;
use App\Application\Regulation\Command\DuplicateRegulationCommandHandler;
use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DuplicateRegulationCommandHandlerTest extends TestCase
{
    private $translator;
    private $canOrganizationAccessToRegulation;
    private $queryBus;
    private $commandBus;
    private $originalRegulationOrderRecord;
    private $organization;
    private $originalRegulationOrder;

    public function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->organization = $this->createMock(Organization::class);
        $this->originalRegulationOrder = $this->createMock(RegulationOrder::class);

        $this->originalRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->originalRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->originalRegulationOrder);
    }

    public function testRegulationFullyDuplicated(): void
    {
        $startDate = new \DateTimeImmutable('2023-03-13');
        $endDate = new \DateTimeImmutable('2023-03-16');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F01/2023');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($startDate);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn($endDate);

        $duplicatedRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('regulation.identifier.copy', [
                '%identifier%' => 'F01/2023',
            ])
            ->willReturn('F01/2023 (copie)');

        // Condition, RegulationOrder and RegulationOrderRecord
        $step1command = new SaveRegulationOrderCommand();
        $step1command->identifier = 'F01/2023 (copie)';
        $step1command->description = 'Description';
        $step1command->startDate = $startDate;
        $step1command->endDate = $endDate;
        $step1command->organization = $this->organization;

        $this->commandBus
            ->expects(self::exactly(1))
            ->method('handle')
            ->with($step1command)
            ->willReturn($duplicatedRegulationOrderRecord);

        $handler = new DuplicateRegulationCommandHandler(
            $this->translator,
            $this->commandBus,
        );

        $command = new DuplicateRegulationCommand($this->organization, $this->originalRegulationOrderRecord);
        $this->assertSame($duplicatedRegulationOrderRecord, $handler($command));
    }
}
