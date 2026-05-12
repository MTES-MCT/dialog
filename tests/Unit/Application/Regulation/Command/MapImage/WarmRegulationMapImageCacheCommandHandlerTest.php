<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\MapImage;

use App\Application\Regulation\Command\MapImage\WarmRegulationMapImageCacheCommand;
use App\Application\Regulation\Command\MapImage\WarmRegulationMapImageCacheCommandHandler;
use App\Domain\Regulation\RegulationMapImageMakerInterface;
use PHPUnit\Framework\TestCase;

final class WarmRegulationMapImageCacheCommandHandlerTest extends TestCase
{
    public function testHandlerDelegatesToImageMaker(): void
    {
        $imageMaker = $this->createMock(RegulationMapImageMakerInterface::class);
        $imageMaker
            ->expects(self::once())
            ->method('make')
            ->with('record-uuid');

        $handler = new WarmRegulationMapImageCacheCommandHandler($imageMaker);
        $handler(new WarmRegulationMapImageCacheCommand('record-uuid'));
    }
}
