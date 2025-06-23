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
            role: 'Adjoint au maire',
            signatoryName: 'Monsieur X',
            organization: $organization,
        );

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $signatoryAuthority->getUuid());
        $this->assertSame($organization, $signatoryAuthority->getOrganization());
        $this->assertSame('Monsieur le maire de Savenay', $signatoryAuthority->getName());
        $this->assertSame('Adjoint au maire', $signatoryAuthority->getRole());
        $this->assertSame('Monsieur X', $signatoryAuthority->getSignatoryName());

        $signatoryAuthority->update(
            name: 'Madame la maire de Savenay',
            role: 'Adjointe au maire',
            signatoryName: 'Madame X',
        );

        $this->assertSame('Madame la maire de Savenay', $signatoryAuthority->getName());
        $this->assertSame('Adjointe au maire', $signatoryAuthority->getRole());
        $this->assertSame('Madame X', $signatoryAuthority->getSignatoryName());
    }
}
