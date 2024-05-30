<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\DTO;

use App\Application\Regulation\DTO\CifsFilterSet;
use PHPUnit\Framework\TestCase;

final class CifsFilterSetTest extends TestCase
{
    public function testConstructor(): void
    {
        $filterSet = new CifsFilterSet();
        $this->assertEquals([], $filterSet->allowedSources);
        $this->assertEquals([], $filterSet->excludedIdentifiers);
        $this->assertEquals([], $filterSet->allowedLocationIds);

        $filterSet = new CifsFilterSet(['source1'], ['identifier1'], ['locationId1']);
        $this->assertEquals(['source1'], $filterSet->allowedSources);
        $this->assertEquals(['identifier1'], $filterSet->excludedIdentifiers);
        $this->assertEquals(['locationId1'], $filterSet->allowedLocationIds);
    }

    public function testFromJSON(): void
    {
        $filterSet = CifsFilterSet::fromJSON([]);
        $this->assertEquals([], $filterSet->allowedSources);
        $this->assertEquals([], $filterSet->excludedIdentifiers);
        $this->assertEquals([], $filterSet->allowedLocationIds);

        $filterSet = CifsFilterSet::fromJSON([
            'allowed_sources' => ['source1'],
            'excluded_identifiers' => ['identifier1'],
            'allowed_location_ids' => ['locationId1'],
        ]);
        $this->assertEquals(['source1'], $filterSet->allowedSources);
        $this->assertEquals(['identifier1'], $filterSet->excludedIdentifiers);
        $this->assertEquals(['locationId1'], $filterSet->allowedLocationIds);
    }
}
