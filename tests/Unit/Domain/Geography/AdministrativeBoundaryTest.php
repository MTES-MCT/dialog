<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Geography;

use App\Domain\Geography\AdministrativeBoundary;
use PHPUnit\Framework\TestCase;

final class AdministrativeBoundaryTest extends TestCase
{
    public function testGetters(): void
    {
        $updatedAt = new \DateTimeImmutable('2026-09-30');
        $geometry = '{"type":"Polygon","coordinates":[[[0,0],[1,0],[1,1],[0,0]]]}';

        $boundary = new AdministrativeBoundary('departement', '44', 'Loire-Atlantique', $geometry, $updatedAt);

        $this->assertSame('departement', $boundary->getCodeType());
        $this->assertSame('44', $boundary->getCode());
        $this->assertSame('Loire-Atlantique', $boundary->getName());
        $this->assertSame($geometry, $boundary->getGeometry());
        $this->assertSame($updatedAt, $boundary->getUpdatedAt());
    }
}
