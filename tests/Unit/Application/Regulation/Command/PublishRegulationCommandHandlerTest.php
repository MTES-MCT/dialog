<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\PublishRegulationCommand;
use App\Application\Regulation\Command\PublishRegulationCommandHandler;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;
use PHPUnit\Framework\TestCase;

final class PublishRegulationCommandHandlerTest extends TestCase
{
    private $canRegulationOrderRecordBePublished;

    protected function setUp(): void
    {
        $this->canRegulationOrderRecordBePublished = $this->createMock(CanRegulationOrderRecordBePublished::class);
    }

    public function testPublish(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateStatus')
            ->with('published');

        $this->canRegulationOrderRecordBePublished
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->willReturn(true);

        $handler = new PublishRegulationCommandHandler(
            $this->canRegulationOrderRecordBePublished,
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
            $this->canRegulationOrderRecordBePublished,
        );

        $command = new PublishRegulationCommand($regulationOrderRecord);
        $handler($command);
    }
}
