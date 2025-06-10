<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Establishment;

use App\Domain\Organization\Establishment\Establishment;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class EstablishmentTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = $this->createMock(Organization::class);

        $establishment = new Establishment(
            'e21361a3-999f-45b7-91d1-12cacac3f15c',
            '12 rue de la Paix',
            '75000',
            'Paris',
            $organization,
        );

        $this->assertSame('e21361a3-999f-45b7-91d1-12cacac3f15c', $establishment->getUuid());
        $this->assertSame('12 rue de la Paix', $establishment->getAddress());
        $this->assertSame('75000', $establishment->getZipCode());
        $this->assertSame('Paris', $establishment->getCity());
        $this->assertSame($organization, $establishment->getOrganization());
        $this->assertNull($establishment->getAddressComplement());
        $this->assertSame('12 rue de la Paix 75000 Paris', (string) $establishment);

        $establishment->update(
            '13 rue de la Paix',
            '75001',
            'Paris 1',
            'Bâtiment A',
        );

        $this->assertSame('13 rue de la Paix', $establishment->getAddress());
        $this->assertSame('75001', $establishment->getZipCode());
        $this->assertSame('Paris 1', $establishment->getCity());
        $this->assertSame('Bâtiment A', $establishment->getAddressComplement());
    }
}
