<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Country\France;

use App\Domain\Country\France\City;
use PHPUnit\Framework\TestCase;

final class CityTest extends TestCase
{
    public function testGetters(): void
    {
        $city = new City(
            inseeCode: '44195',
            name: 'Savenay',
            departement: '44',
        );

        $this->assertSame('44195', $city->getInseeCode());
        $this->assertSame('Savenay', $city->getName());
        $this->assertSame('44', $city->getDepartement());
    }
}
