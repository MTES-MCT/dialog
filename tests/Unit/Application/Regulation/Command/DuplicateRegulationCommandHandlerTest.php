<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DuplicateMeasureCommand;
use App\Application\Regulation\Command\DuplicateRegulationCommand;
use App\Application\Regulation\Command\DuplicateRegulationCommandHandler;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Query\GetDuplicateIdentifierQuery;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class DuplicateRegulationCommandHandlerTest extends TestCase
{
    private $queryBus;
    private $commandBus;
    private $originalRegulationOrderRecord;
    private $originalRegulationOrder;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->originalRegulationOrder = $this->createMock(RegulationOrder::class);
        $this->originalRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
    }

    public function testRegulationFullyDuplicated(): void
    {
        $measure1 = $this->createMock(Measure::class);
        $measure2 = $this->createMock(Measure::class);
        $duplicatedRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $originalOrganization = $this->createMock(Organization::class);

        $this->originalRegulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($originalOrganization);

        $this->originalRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->originalRegulationOrder);

        $regulationOrderTemplate = $this->createMock(RegulationOrderTemplate::class);
        $regulationOrderTemplate
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('P67f7f275-51b2-4f7f-914a-45168a28d4c2');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderTemplate')
            ->willReturn($regulationOrderTemplate);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F01/2023');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getTitle')
            ->willReturn('title');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getCategory')
            ->willReturn(RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getOtherCategoryText')
            ->willReturn(null);

        $this->originalRegulationOrder
            ->expects(self::exactly(2))
            ->method('getMeasures')
            ->willReturn([$measure1, $measure2]);

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetDuplicateIdentifierQuery('F01/2023', $originalOrganization))
            ->willReturn('F01/2023-1');

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = 'F01/2023-1';
        $generalInfoCommand->title = 'title';
        $generalInfoCommand->category = RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value;
        $generalInfoCommand->organization = $originalOrganization;
        $generalInfoCommand->regulationOrderTemplateUuid = 'P67f7f275-51b2-4f7f-914a-45168a28d4c2';

        $duplicateMeasureCommand1 = new DuplicateMeasureCommand($measure1, $duplicatedRegulationOrderRecord);
        $duplicateMeasureCommand2 = new DuplicateMeasureCommand($measure2, $duplicatedRegulationOrderRecord);
        $matcher = self::exactly(3);

        $this->commandBus
            ->expects($matcher)
            ->method('handle')->willReturnCallback(function (...$parameters) use ($matcher, $generalInfoCommand, $duplicateMeasureCommand1, $duplicateMeasureCommand2, $duplicatedRegulationOrderRecord) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals($generalInfoCommand, $parameters[0]);

                    return $duplicatedRegulationOrderRecord;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals($duplicateMeasureCommand1, $parameters[0]);

                    return;
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertEquals($duplicateMeasureCommand2, $parameters[0]);

                    return;
                }
            });

        $handler = new DuplicateRegulationCommandHandler(
            $this->queryBus,
            $this->commandBus,
        );

        $command = new DuplicateRegulationCommand($this->originalRegulationOrderRecord);
        $this->assertSame($duplicatedRegulationOrderRecord, $handler($command));
    }
}
