<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\CreateRegulationOrderHistoryCommand;
use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\PublishRegulationCommandHandler;
use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderHistory;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;
use App\Domain\User\User;
use App\Infrastructure\Security\AuthenticatedUser;
use PHPUnit\Framework\TestCase;

final class PublishRegulationCommandHandlerTest extends TestCase
{
    private $canRegulationOrderRecordBePublished;
    private $commandBus;
    private $authenticatedUser;

    protected function setUp(): void
    {
        $this->canRegulationOrderRecordBePublished = $this->createMock(CanRegulationOrderRecordBePublished::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->authenticatedUser = $this->createMock(AuthenticatedUser::class);
    }

    public function testPublish(): void
    {
        $createdRegulationOrderHistory = $this->createMock(RegulationOrderHistory::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $user = $this->createMock(User::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateStatus')
            ->with('published');

        $regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);

        $this->authenticatedUser
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->canRegulationOrderRecordBePublished
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->willReturn(true);

        $action = ActionTypeEnum::PUBLISH->value;
        $regulationOrderHistoryCommand = new CreateRegulationOrderHistoryCommand($regulationOrder, $user, $action);

        $this->commandBus
                ->expects(self::once())
                ->method('handle')
                ->with($this->equalTo($regulationOrderHistoryCommand));

        $handler = new PublishRegulationCommandHandler(
            $this->canRegulationOrderRecordBePublished, $this->commandBus, $this->authenticatedUser,
        );

        $command = new PublishRegulationCommand($regulationOrderRecord);
        $this->assertEmpty($handler($command));
    }

    public function testRegulationCannotBePublished(): void
    {
        $this->expectException(RegulationOrderRecordCannotBePublishedException::class);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::never())
            ->method('updateStatus');

        $this->canRegulationOrderRecordBePublished
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($regulationOrderRecord)
            ->willReturn(false);

        $handler = new PublishRegulationCommandHandler(
            $this->canRegulationOrderRecordBePublished, $this->commandBus, $this->authenticatedUser,
        );

        $command = new PublishRegulationCommand($regulationOrderRecord);
        $handler($command);
    }
}
