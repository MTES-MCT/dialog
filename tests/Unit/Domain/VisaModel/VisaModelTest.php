<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\VisaModel;

use App\Domain\User\Organization;
use App\Domain\VisaModel\VisaModel;
use PHPUnit\Framework\TestCase;

final class VisaModelTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = $this->createMock(Organization::class);

        $visaModel = new VisaModel(
            uuid: '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            name: 'Réglementation de circulation',
            visas: ['Vu que 1', 'Vu que 2'],
            description: 'Limitation de vitesse dans la commune',
            organization: $organization,
        );

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $visaModel->getUuid());
        $this->assertSame('Réglementation de circulation', $visaModel->getName());
        $this->assertSame('Limitation de vitesse dans la commune', $visaModel->getDescription());
        $this->assertSame($organization, $visaModel->getOrganization());
        $this->assertSame(['Vu que 1', 'Vu que 2'], $visaModel->getVisas());

        $visaModel->update('Réglementation', ['Vu que 3'], 'Limitation à 30kmh.');
        $this->assertSame('Réglementation', $visaModel->getName());
        $this->assertSame('Limitation à 30kmh.', $visaModel->getDescription());
        $this->assertSame(['Vu que 3'], $visaModel->getVisas());
    }
}
