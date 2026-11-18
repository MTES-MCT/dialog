<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Anct;

use App\Domain\Anct\TerritoryContact;
use PHPUnit\Framework\TestCase;

final class TerritoryContactTest extends TestCase
{
    public function testGetters(): void
    {
        $territoryContact = new TerritoryContact('21930001000019', 'contact@ville-test.fr');

        $this->assertSame('21930001000019', $territoryContact->getSiret());
        $this->assertSame('contact@ville-test.fr', $territoryContact->getEmail());
    }
}
