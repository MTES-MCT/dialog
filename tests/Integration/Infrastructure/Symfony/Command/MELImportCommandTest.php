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
        $this->assertStringContainsString("Emprises d'intérêt récupérées dans Litteralis : 2", $output);
        $this->assertStringContainsString("Emprises effectivement candidates à l'import : 2 (dans 1 arrêtés)", $output);
        $this->assertStringContainsString('Emprises importées avec succès : 2 (dans 1 arrêtés)', $output);
    }
}