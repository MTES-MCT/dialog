<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\ReportAddressLocation;
use PHPUnit\Framework\TestCase;

final class ReportAddressLocationTest extends TestCase
{
    public function testFromAddressPartsWithNumberedRoad(): void
    {
        $location = ReportAddressLocation::fromAddressParts(
            administrator: 'Route départementale',
            roadNumber: 'D12',
        );

        self::assertSame('Route départementale - D12', (string) $location);
    }

    public function testFromAddressPartsWithNumberedRoadOnlyAdministrator(): void
    {
        $location = ReportAddressLocation::fromAddressParts(
            administrator: 'Route départementale',
        );

        self::assertSame('Route départementale', (string) $location);
    }

    public function testFromAddressPartsWithNumberedRoadOnlyRoadNumber(): void
    {
        $location = ReportAddressLocation::fromAddressParts(
            roadNumber: 'D12',
        );

        self::assertSame('D12', (string) $location);
    }

    public function testFromAddressPartsWithNamedStreet(): void
    {
        $location = ReportAddressLocation::fromAddressParts(
            cityLabel: 'Paris',
            roadName: 'Rue de la Paix',
        );

        self::assertSame('Paris - Rue de la Paix', (string) $location);
    }

    public function testFromAddressPartsWithNamedStreetOnlyCityLabel(): void
    {
        $location = ReportAddressLocation::fromAddressParts(
            cityLabel: 'Paris',
        );

        self::assertSame('Paris', (string) $location);
    }

    public function testFromAddressPartsWithNamedStreetOnlyRoadName(): void
    {
        $location = ReportAddressLocation::fromAddressParts(
            roadName: 'Rue de la Paix',
        );

        self::assertSame('Rue de la Paix', (string) $location);
    }

    public function testFromAddressPartsWithEmptyStrings(): void
    {
        $location = ReportAddressLocation::fromAddressParts(
            administrator: '',
            roadNumber: '',
            cityLabel: '',
            roadName: '',
        );

        self::assertNull($location);
    }

    public function testFromAddressPartsWithNullParameters(): void
    {
        $location = ReportAddressLocation::fromAddressParts();

        self::assertNull($location);
    }
}
