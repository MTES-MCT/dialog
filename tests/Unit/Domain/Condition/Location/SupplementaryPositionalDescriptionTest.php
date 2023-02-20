<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition\Location;

use App\Domain\Condition\Location\Location;
use App\Domain\Condition\Location\SupplementaryPositionalDescription;
use PHPUnit\Framework\TestCase;

final class SupplementaryPositionalDescriptionTest extends TestCase
{
    public function testGetters(): void
    {
        $location = $this->createMock(Location::class);
        $supplementaryPositionalDescription = new SupplementaryPositionalDescription(
            'b4812143-c4d8-44e6-8c3a-34688becae6e',
            $location,
        );

        $this->assertSame('b4812143-c4d8-44e6-8c3a-34688becae6e', $supplementaryPositionalDescription->getUuid());
        $this->assertSame($location, $supplementaryPositionalDescription->getLocation());
        $this->assertSame([], $supplementaryPositionalDescription->getRoadInformations()); // Automatically set by Doctrine
    }
}
