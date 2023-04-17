<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\Exception\LocationAddressParsingException;
use App\Domain\Regulation\LocationAddress;
use PHPUnit\Framework\TestCase;

final class LocationAddressTest extends TestCase
{
    public function testParsingFailure(): void
    {
        $this->expectException(LocationAddressParsingException::class);
        $this->expectExceptionMessageMatches("/^Address 'This is not a valid address' did not have expected format/");
        LocationAddress::fromString('This is not a valid address');
    }

    private function provideParseRoad(): array
    {
        return [
            // General case
            ['Rue du Grand Brossais, 44260 Savenay', '44260', 'Savenay', 'Rue du Grand Brossais', 'Rue du Grand Brossais, 44260 Savenay'],
            // House number is ignored
            ['16 Rue du Grand Brossais, 44260 Savenay', '44260', 'Savenay', 'Rue du Grand Brossais', 'Rue du Grand Brossais, 44260 Savenay'],
            // Presence or absence of ',' separator does not matter
            ['Rue du Grand Brossais 44260 Savenay', '44260', 'Savenay', 'Rue du Grand Brossais', 'Rue du Grand Brossais, 44260 Savenay'],
        ];
    }

    /**
     * @dataProvider provideParseRoad
     */
    public function testParseRoad(string $address, string $postCode, string $city, string $roadName, string $string): void
    {
        $locationAddress = LocationAddress::fromString($address);
        $this->assertSame($postCode, $locationAddress->getPostCode());
        $this->assertSame($city, $locationAddress->getCity());
        $this->assertSame($roadName, $locationAddress->getRoadName());
        $this->assertSame($string, (string) $locationAddress);
    }

    public function testParseCity(): void
    {
        $locationAddress = LocationAddress::fromString('75002 Paris 2e Arrondissement');
        $this->assertSame('75002', $locationAddress->getPostCode());
        $this->assertSame('Paris 2e Arrondissement', $locationAddress->getCity());
        $this->assertNull($locationAddress->getRoadName());
        $this->assertSame('75002 Paris 2e Arrondissement', (string) $locationAddress);
    }
}
