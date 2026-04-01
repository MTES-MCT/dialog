<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\GenerateDatexCommand;
use App\Application\Regulation\Command\GenerateDatexCommandHandler;
use App\Application\Regulation\DatexGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class GenerateDatexCommandHandlerTest extends TestCase
{
    public function testHandle(): void
    {
        $datexGenerator = $this->createMock(DatexGeneratorInterface::class);
        $datexGenerator
            ->expects(self::once())
            ->method('generate');

        $handler = new GenerateDatexCommandHandler($datexGenerator);
        $handler(new GenerateDatexCommand());
    }
}
