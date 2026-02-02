<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\Command\LitteralisImportCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class LitteralisImportCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $command = $container->get(LitteralisImportCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful($commandTester->getDisplay());

        $output = $commandTester->getDisplay();

        // These results depend on LitteralisMockHttpClient.data.json
        $this->assertStringContainsString("Nombre total d'emprises dans Litteralis pour cette organisation : 2", $output);
        $this->assertStringContainsString("Emprises d'intérêt récupérées dans Litteralis : 2", $output);
        $this->assertStringContainsString("Emprises effectivement candidates à l'import : 3 (dans 2 arrêtés)", $output);
        $this->assertStringContainsString('Emprises importées avec succès : 3 (dans 2 arrêtés)', $output);
    }
}
