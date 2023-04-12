<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\View\DetailLocationView;
use PHPUnit\Framework\TestCase;

final class DetailLocationViewTest extends TestCase
{
    public function testParsingFailure(): void
    {
        $this->expectExceptionMessageMatches("/^Address 'this is not a valid address' did not have expected format/i");
        new DetailLocationView('This is not a valid address', null, null);
    }

    private function provideGetters(): array
    {
        return [
            ['Rue du Grand Brossais, 44260 Savenay', 'Savenay', '44260', 'Rue du Grand Brossais'],
            // House number is ignored
            ['16 Rue du Grand Brossais, 44260 Savenay', 'Savenay', '44260', 'Rue du Grand Brossais'],
            // Presence or absence of ',' separator does not matter
            ['Rue du Grand Brossais 44260 Savenay', 'Savenay', '44260', 'Rue du Grand Brossais'],
        ];
    }

    /**
     * @dataProvider provideGetters
     */
    public function testGetters(string $address, string $city, string $postCode, string $roadName): void
    {
        $view = new DetailLocationView($address, null, null);
        $this->assertSame($city, $view->getCity());
        $this->assertSame($postCode, $view->getPostCode());
        $this->assertSame($roadName, $view->getRoadName());
    }
}
