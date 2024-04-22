<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Location;

use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NumberedRoad;
use PHPUnit\Framework\TestCase;

final class NumberedRoadTest extends TestCase
{
    public function testGetters(): void
    {
        $location = $this->createMock(Location::class);

        $numberedRoad = new NumberedRoad(
            uuid: '8785a4c2-8f0d-423e-bd5b-641f228df23b',
            location: $location,
            administrator: 'Ardèche',
            roadNumber: 'D110',
            fromPointNumber: '14',
            fromAbscissa: 650,
            fromSide: 'U',
            toPointNumber: '16',
            toAbscissa: 250,
            toSide: 'U',
        );

        $this->assertSame('8785a4c2-8f0d-423e-bd5b-641f228df23b', $numberedRoad->getUuid());
        $this->assertSame($location, $numberedRoad->getLocation());
        $this->assertSame('Ardèche', $numberedRoad->getAdministrator());
        $this->assertSame('D110', $numberedRoad->getRoadNumber());
        $this->assertSame('14', $numberedRoad->getFromPointNumber());
        $this->assertSame(650, $numberedRoad->getFromAbscissa());
        $this->assertSame('U', $numberedRoad->getFromSide());
        $this->assertSame('16', $numberedRoad->getToPointNumber());
        $this->assertSame(250, $numberedRoad->getToAbscissa());
        $this->assertSame('U', $numberedRoad->getToSide());

        $numberedRoad->update(
            'Ain',
            'D16',
            '10',
            'D',
            0,
            '12',
            'D',
            0,
        );

        $this->assertSame('Ain', $numberedRoad->getAdministrator());
        $this->assertSame('D16', $numberedRoad->getNumberedRoadNumber());
        $this->assertSame('10', $numberedRoad->getFromPointNumber());
        $this->assertSame('D', $numberedRoad->getFromSide());
        $this->assertSame(0, $numberedRoad->getFromAbscissa());
        $this->assertSame('12', $numberedRoad->getToPointNumber());
        $this->assertSame(0, $numberedRoad->getToAbscissa());
        $this->assertSame('D', $numberedRoad->getToSide());
    }
}
