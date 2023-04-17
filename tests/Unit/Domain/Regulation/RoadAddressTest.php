<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\Exception\RoadAddressParsingException;
use App\Domain\Regulation\RoadAddress;
use PHPUnit\Framework\TestCase;

final class RoadAddressTest extends TestCase
{
    public function testParsingFailure(): void
    {
        $this->expectException(RoadAddressParsingException::class);
        $this->expectExceptionMessageMatches("/^Address 'This is not a valid address' did not have expected format/");
        RoadAddress::fromString('This is not a valid address');
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
        $roadAddress = RoadAddress::fromString($address);
        $this->assertSame($postCode, $roadAddress->getPostCode());
        $this->assertSame($city, $roadAddress->getCity());
        $this->assertSame($roadName, $roadAddress->getRoadName());
    }
}
