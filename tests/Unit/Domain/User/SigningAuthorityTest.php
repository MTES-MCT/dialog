<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\Organization\SigningAuthority\SigningAuthority;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class SigningAuthorityTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = $this->createMock(Organization::class);

        $signatoryAuthority = new SigningAuthority(
            uuid: '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            name: 'Monsieur le maire de Savenay',
            address: '3 rue de la Concertation',
            placeOfSignature: 'Savenay',
            signatoryName: 'Monsieur X, Maire de Savenay',
            organization: $organization,
        );

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $signatoryAuthority->getUuid());
        $this->assertSame($organization, $signatoryAuthority->getOrganization());
        $this->assertSame('Monsieur le maire de Savenay', $signatoryAuthority->getName());
        $this->assertSame('Savenay', $signatoryAuthority->getPlaceOfSignature());
        $this->assertSame('Monsieur X, Maire de Savenay', $signatoryAuthority->getSignatoryName());
        $this->assertSame('3 rue de la Concertation', $signatoryAuthority->getAddress());

        $signatoryAuthority->update(
            name: 'Madame la maire de Savenay',
            address: '4 rue de la Concertation',
            placeOfSignature: 'Savenay 2',
            signatoryName: 'Madame X, Maire de Savenay',
        );

        $this->assertSame('Madame la maire de Savenay', $signatoryAuthority->getName());
        $this->assertSame('Savenay 2', $signatoryAuthority->getPlaceOfSignature());
        $this->assertSame('Madame X, Maire de Savenay', $signatoryAuthority->getSignatoryName());
        $this->assertSame('4 rue de la Concertation', $signatoryAuthority->getAddress());
    }
}
