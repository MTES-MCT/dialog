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

    private function provideParse(): array
    {
        return [
            // General case
            ['Rue du Grand Brossais, 44260 Savenay', '44260', 'Savenay', 'Rue du Grand Brossais'],
            // House number is ignored
            ['16 Rue du Grand Brossais, 44260 Savenay', '44260', 'Savenay', 'Rue du Grand Brossais'],
            // Presence or absence of ',' separator does not matter
            ['Rue du Grand Brossais 44260 Savenay', '44260', 'Savenay', 'Rue du Grand Brossais'],
        ];
    }

    /**
     * @dataProvider provideParse
     */
    public function testParse(string $address, string $postCode, string $city, string $roadName): void
    {
        $locationAddress = LocationAddress::fromString($address);
        $this->assertSame($postCode, $locationAddress->getPostCode());
        $this->assertSame($city, $locationAddress->getCity());
        $this->assertSame($roadName, $locationAddress->getRoadName());
    }
}
