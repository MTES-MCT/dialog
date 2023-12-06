<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\View;

use App\Application\Regulation\View\DatexLocationView;
use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use PHPUnit\Framework\TestCase;

final class DatexLocationViewTest extends TestCase
{
    public function testGmlPosList(): void
    {
        $geometry = GeoJSON::toLineString([
            Coordinates::fromLonLat(-1.935836, 47.347024),
            Coordinates::fromLonLat(-1.930973, 47.347917),
        ]);

        $view = new DatexLocationView(
            'Route du Grand Brossais',
            $geometry,
        );

        $this->assertSame($view->roadName, 'Route du Grand Brossais');
        $this->assertSame($view->geometry, $geometry);
        $this->assertSame($view->gmlPosList, '-1.935836 47.347024 -1.930973 47.347917');
    }
}
