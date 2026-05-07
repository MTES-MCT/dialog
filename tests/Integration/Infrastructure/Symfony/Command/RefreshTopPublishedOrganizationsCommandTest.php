<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\Command\RefreshTopPublishedOrganizationsCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class RefreshTopPublishedOrganizationsCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $command = $container->get(RefreshTopPublishedOrganizationsCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $this->assertStringContainsString('top_published_organization refreshed.', $commandTester->getDisplay());
    }
}
