<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Location;

use App\Domain\Condition\Location\RoadInformation;
use App\Domain\Condition\Location\SupplementaryPositionalDescription;
use PHPUnit\Framework\TestCase;

final class RoadInformationTest extends TestCase
{
    public function testGetters(): void
    {
        $supplementaryPositionalDescription = $this->createMock(SupplementaryPositionalDescription::class);
        $roadInformation = new RoadInformation(
            'b4812143-c4d8-44e6-8c3a-34688becae6e',
            $supplementaryPositionalDescription,
            'Lille',
            'A1',
            'Autoroute du Nord',
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $roadInformation->getUuid());
        $this->assertSame($supplementaryPositionalDescription, $roadInformation->getSupplementaryPositionalDescription());
        $this->assertSame('Lille', $roadInformation->getRoadDestination());
        $this->assertSame('Autoroute du Nord', $roadInformation->getRoadName());
        $this->assertSame('A1', $roadInformation->getRoadNumber());
    }
}
