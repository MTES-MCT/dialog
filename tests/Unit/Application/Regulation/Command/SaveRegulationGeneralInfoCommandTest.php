<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class SaveRegulationGeneralInfoCommandTest extends TestCase
{
    public function testWithoutRegulationOrderRecord(): void
    {
        $command = SaveRegulationGeneralInfoCommand::create();

        $this->assertEmpty($command->identifier);
        $this->assertSame(RegulationOrderRecordSourceEnum::DIALOG->value, $command->source);
        $this->assertEmpty($command->organization);
        $this->assertEmpty($command->description);
        $this->assertEmpty($command->startDate);
        $this->assertEmpty($command->endDate);
    }

    public function testWithRegulationOrderRecord(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $start = new \DateTimeImmutable('2023-03-13');
        $end = new \DateTimeImmutable('2023-03-15');

        $regulationOrder
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F02/2023');

        $regulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');

        $regulationOrder
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($start);

        $regulationOrder
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn($end);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);

        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $regulationOrderRecord
            ->expects(self::once())
            ->method('getSource')
            ->willReturn('my_source');

        $command = SaveRegulationGeneralInfoCommand::create($regulationOrderRecord);

        $this->assertSame($command->identifier, 'F02/2023');
        $this->assertSame('my_source', $command->source);
        $this->assertSame($command->description, 'Description');
        $this->assertSame($command->startDate, $start);
        $this->assertSame($command->endDate, $end);
        $this->assertSame($command->organization, $organization);
    }

    public function testCleanOtherCategoryText(): void
    {
        $command = new SaveRegulationGeneralInfoCommand();
        $command->category = RegulationOrderCategoryEnum::EVENT->value;
        $command->otherCategoryText = 'Will be cleared';
        $command->cleanOtherCategoryText();
        $this->assertNull($command->otherCategoryText);

        $command = new SaveRegulationGeneralInfoCommand();
        $command->category = RegulationOrderCategoryEnum::OTHER->value;
        $command->otherCategoryText = 'Will be kept';
        $command->cleanOtherCategoryText();
        $this->assertSame('Will be kept', $command->otherCategoryText);
    }
}
