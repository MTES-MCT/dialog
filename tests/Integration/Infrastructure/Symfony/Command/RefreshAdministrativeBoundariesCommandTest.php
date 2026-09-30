<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Application\Geography\View\AdministrativeBoundaryView;
use App\Domain\Geography\Repository\AdministrativeBoundaryRepositoryInterface;
use App\Infrastructure\Symfony\Command\RefreshAdministrativeBoundariesCommand;
use App\Tests\Mock\IgnGeocoderMockClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class RefreshAdministrativeBoundariesCommandTest extends KernelTestCase
{
    private const OUTDATED_GEOMETRY = '{"type":"Polygon","coordinates":[[[0,0],[1,0],[1,1],[0,1],[0,0]]]}';

    private AdministrativeBoundaryRepositoryInterface $repository;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->repository = $container->get(AdministrativeBoundaryRepositoryInterface::class);
        $this->commandTester = new CommandTester($container->get(RefreshAdministrativeBoundariesCommand::class));
    }

    public function testExecuteWithoutStoredBoundary(): void
    {
        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Administrative boundaries: 0 refreshed, 0 removed, 0 failed.', $this->commandTester->getDisplay());
    }

    public function testExecuteRefreshesStoredBoundariesAndRemovesUnknownOnes(): void
    {
        $outdated = new \DateTimeImmutable('2025-01-01');
        $this->repository->save('departement', '93', 'Ancien nom', self::OUTDATED_GEOMETRY, $outdated);
        // Collectivité disparue du COG.
        $this->repository->save('epci', '200000000', 'EPCI dissous', self::OUTDATED_GEOMETRY, $outdated);

        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Administrative boundaries: 1 refreshed, 1 removed, 0 failed.', $this->commandTester->getDisplay());

        $this->assertEquals(
            [new AdministrativeBoundaryView('departement', '93', 'Seine-Saint-Denis')],
            $this->repository->findAllViews(),
        );
        // Le contour a été remplacé par celui de la source.
        $this->assertSame(2.28, $this->repository->findMapBbox('departement', '93')->minLon);
    }

    public function testExecuteKeepsBoundaryWhenSourceIsUnavailable(): void
    {
        $outdated = new \DateTimeImmutable('2025-01-01');
        $this->repository->save('departement', IgnGeocoderMockClient::UNAVAILABLE_DEPARTMENT_CODE, 'Département', self::OUTDATED_GEOMETRY, $outdated);
        $this->repository->save('region', '11', 'Ancien nom', self::OUTDATED_GEOMETRY, $outdated);

        $this->assertSame(Command::FAILURE, $this->commandTester->execute([]));

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Failed to fetch administrative boundary "500" of type "departement"', $display);
        $this->assertStringContainsString('Administrative boundaries: 1 refreshed, 0 removed, 1 failed.', $display);

        // Le contour qui n'a pas pu être rafraîchi est conservé tel quel.
        $this->assertEquals(
            [
                new AdministrativeBoundaryView('departement', '500', 'Département'),
                new AdministrativeBoundaryView('region', '11', 'Île-de-France'),
            ],
            $this->repository->findAllViews(),
        );
    }
}
