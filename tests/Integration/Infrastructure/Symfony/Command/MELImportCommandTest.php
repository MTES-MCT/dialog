<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\Command\MELImportCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class MELImportCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $command = $container->get(MELImportCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful($commandTester->getDisplay());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Nombre total d'emprises dans Litteralis pour cette organisation : 2", $output);
        $this->assertStringContainsString("Nombre d'emprises d'intérêt récupérées par l'API Litteralis : 2", $output);
        $this->assertStringContainsString("Nombre d'emprises extraites effectivement candidates à l'import : 2", $output);
    }
}
