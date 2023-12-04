<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Symfony\Command;

use App\Application\GeocoderInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Symfony\Command\GeocodeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GeocodeCommandTest extends TestCase
{
    private $geocoder;
    private $roadGeocoder;
    private $command;
    private $commandTester;

    protected function setUp(): void
    {
        $this->geocoder = $this->createMock(GeocoderInterface::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->command = new GeocodeCommand($this->geocoder, $this->roadGeocoder);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommand()
    {
        $this->assertSame('app:geocode', $this->command->getName());
    }

    public function testExecute()
    {
        $this->geocoder
            ->expects(self::once())
            ->method('computeCoordinates')
            ->with('3 Rue des Tournesols 82000 Montauban')
            ->willReturn(Coordinates::fromLonLat(1.386715, 44.049081));

        $this->roadGeocoder
            ->expects(self::never())
            ->method('computeRoadLine');

        $this->commandTester->execute([
            'address' => '3 Rue des Tournesols 82000 Montauban',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('POINT(1.386715 44.049081)', $output);
    }

    public function testExecuteFullRoad(): void
    {
        $this->geocoder
            ->expects(self::never())
            ->method('computeCoordinates');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoadLine')
            ->with('Rue des Tournesols')
            ->willReturn('...geometry...');

        $this->commandTester->execute([
            'address' => 'Rue des Tournesols 82000 Montauban',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('...geometry...', $output);
    }
}
