<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\Command\LoadFrCityDataCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class LoadFrCityDataCommandTest extends KernelTestCase
{
    /**      * @group only      */ 
    public function testExecute(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $command = $container->get(LoadFrCityDataCommand::class);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        
        $commandTester->assertCommandIsSuccessful();
    }
}
