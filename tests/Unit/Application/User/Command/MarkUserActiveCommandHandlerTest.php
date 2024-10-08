<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\User\Command\MarkUserActiveCommand;
use App\Application\User\Command\MarkUserActiveCommandHandler;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class MarkUserActiveCommandHandlerTest extends TestCase
{
    public function testHandler(): void
    {
        $user = $this->createMock(User::class);
        $dateUtils = $this->createMock(DateUtilsInterface::class);

        $now = new \DateTimeImmutable('2024-10-01');

        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $command = new MarkUserActiveCommand($user);
        $handler = new MarkUserActiveCommandHandler($dateUtils);
        $handler($command);
    }
}
