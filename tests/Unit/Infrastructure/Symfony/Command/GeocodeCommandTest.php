<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Symfony\Command;

use App\Application\GeocoderInterface;
use App\Application\GeographyFormatterInterface;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Symfony\Command\GeocodeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GeocodeCommandTest extends TestCase
{
    public function testExecute()
    {
        $geocoder = $this->createMock(GeocoderInterface::class);
        $geographyFormatter = $this->createMock(GeographyFormatterInterface::class);

        $geocoder
            ->expects(self::once())
            ->method('computeCoordinates')
            ->with('3 Rue des Tournesols 82000 Montauban')
            ->willReturn(Coordinates::fromLatLon(44.049081, 1.386715));

        $geographyFormatter
            ->expects(self::once())
            ->method('formatPoint')
            ->willReturn('POINT(44.049081 1.386715)');

        $command = new GeocodeCommand($geocoder, $geographyFormatter);
        $commandTester = new CommandTester($command);

        $this->assertSame('app:geocode', $command->getName());

        $commandTester->execute([
            'address' => '3 Rue des Tournesols 82000 Montauban',
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('POINT(44.049081 1.386715)', $output);
    }
}
