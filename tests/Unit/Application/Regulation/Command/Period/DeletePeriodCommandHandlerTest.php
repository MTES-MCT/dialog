<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\Regulation\Command\Period\DeletePeriodCommand;
use App\Application\Regulation\Command\Period\DeletePeriodCommandHandler;
use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeletePeriodCommandHandlerTest extends TestCase
{
    private $period;
    private $periodRepository;

    protected function setUp(): void
    {
        $this->period = $this->createMock(Period::class);
        $this->periodRepository = $this->createMock(PeriodRepositoryInterface::class);
    }

    public function testDelete(): void
    {
        $this->periodRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->period));

        $handler = new DeletePeriodCommandHandler($this->periodRepository);

        $command = new DeletePeriodCommand($this->period);
        $this->assertEmpty($handler($command));
    }
}
