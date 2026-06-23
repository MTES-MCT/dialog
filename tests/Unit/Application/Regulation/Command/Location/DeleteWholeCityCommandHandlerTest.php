<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\DeleteWholeCityCommand;
use App\Application\Regulation\Command\Location\DeleteWholeCityCommandHandler;
use App\Domain\Regulation\Location\WholeCity;
use App\Domain\Regulation\Repository\WholeCityRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteWholeCityCommandHandlerTest extends TestCase
{
    public function testDelete(): void
    {
        $wholeCity = $this->createMock(WholeCity::class);

        $wholeCityRepository = $this->createMock(WholeCityRepositoryInterface::class);
        $wholeCityRepository
            ->expects(self::once())
            ->method('delete')
            ->with($wholeCity);

        $handler = new DeleteWholeCityCommandHandler($wholeCityRepository);
        $handler(new DeleteWholeCityCommand($wholeCity));
    }
}
