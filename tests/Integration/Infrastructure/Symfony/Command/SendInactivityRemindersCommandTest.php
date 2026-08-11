<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\Command\SendInactivityRemindersCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SendInactivityRemindersCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $command = $container->get(SendInactivityRemindersCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }
}
