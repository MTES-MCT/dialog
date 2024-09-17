<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Visa;

use App\Domain\User\Organization;
use App\Domain\Visa\VisaModel;
use PHPUnit\Framework\TestCase;

final class VisaModelTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = $this->createMock(Organization::class);

        $visa = new VisaModel(
            uuid: '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            name: 'Réglementation de circulation',
            visas: ['Vu que 1', 'Vu que 2'],
            description: 'Limitation de vitesse dans la commune',
            organization: $organization,
        );

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $visa->getUuid());
        $this->assertSame('Réglementation de circulation', $visa->getName());
        $this->assertSame('Limitation de vitesse dans la commune', $visa->getDescription());
        $this->assertSame($organization, $visa->getOrganization());
        $this->assertSame(['Vu que 1', 'Vu que 2'], $visa->getVisas());
    }
}
