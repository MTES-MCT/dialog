<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Domain\Anct\Repository\TerritoryContactRepositoryInterface;
use App\Infrastructure\Symfony\Command\AnctImportCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class AnctImportCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $territoryContactRepository = $container->get(TerritoryContactRepositoryInterface::class);

        $command = $container->get(AnctImportCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--file' => __DIR__ . '/../../../../fixtures/anct/dpnt_sample.json', '--min-rows' => '1']);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('2 collectivités', $commandTester->getDisplay());

        // E-mail is normalized (lowercased) on import.
        $this->assertTrue($territoryContactRepository->isWhitelisted('21930001000019', 'contact@ville-test.fr'));
        $this->assertTrue($territoryContactRepository->isWhitelisted('20005550700012', 'courrier@collectivitedemartinique.mq'));

        // Wrong e-mail for the right SIRET is not whitelisted.
        $this->assertFalse($territoryContactRepository->isWhitelisted('21930001000019', 'someone@ville-test.fr'));

        // Territories without an e-mail or without a SIRET are skipped.
        $this->assertFalse($territoryContactRepository->isWhitelisted('21930002000018', 'anything@example.fr'));
    }
}
