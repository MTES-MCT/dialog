<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Domain\Country\France\Repository\CityRepositoryInterface;
use App\Infrastructure\Symfony\Command\LoadFrCityDataCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class LoadFrCityDataCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $cityRepository = $container->get(CityRepositoryInterface::class);

        $command = $container->get(LoadFrCityDataCommand::class);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        // Test Maisons Laffitte
        $name = 'Maisons-Laffitte';
        $departement = '78';
        $inseeCodeExpected = '78358';

        $result = $cityRepository->findOneByNameAndDepartement($name, $departement);

        $this->assertSame($inseeCodeExpected, $result->getInseeCode());

        // Test Plusieurs villes avec mêmes noms
        $name = 'Romain';
        $departement = '51';
        $inseeCodeExpected = '51464';

        $result = $cityRepository->findOneByNameAndDepartement($name, $departement);

        $this->assertSame($inseeCodeExpected, $result->getInseeCode());

        $name = 'Romain';
        $departement = '39';
        $inseeCodeExpected = '39464';

        $result = $cityRepository->findOneByNameAndDepartement($name, $departement);

        $this->assertSame($inseeCodeExpected, $result->getInseeCode());

        // Cas de ville "Romain qui n'existe pas"
        $name = 'Romain';
        $departement = '17';
        $result = $cityRepository->findOneByNameAndDepartement($name, $departement);

        $this->assertNull($result);
    }
}
