<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Integration\Litteralis\Command;

use App\Application\CommandBusInterface;
use App\Application\Integration\Litteralis\Command\CleanUpLitteralisRegulationsBeforeImportCommand;
use App\Application\Integration\Litteralis\Command\CleanUpLitteralisRegulationsBeforeImportCommandHandler;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CleanUpLitteralisRegulationsBeforeImportCommandHandlerTest extends TestCase
{
    private $commandBus;
    private $regulationOrderRecordRepository;
    private CleanUpLitteralisRegulationsBeforeImportCommandHandler $handler;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->handler = new CleanUpLitteralisRegulationsBeforeImportCommandHandler($this->commandBus, $this->regulationOrderRecordRepository);
    }

    public function testCommand(): void
    {
        $organizationId = '066c3399-6122-7360-8000-cebd52bbd473';
        $laterThan = new \DateTimeImmutable('now');

        $command = new CleanUpLitteralisRegulationsBeforeImportCommand($organizationId, $laterThan);

        $regulationOrderRecord1 = $this->createMock(RegulationOrderRecord::class);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findRegulationOrdersForLitteralisCleanUp')
            ->willReturn([$regulationOrderRecord1]);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(new DeleteRegulationCommand([$organizationId], $regulationOrderRecord1));

        ($this->handler)($command);
    }
}
