<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\AccessRequest;
use PHPUnit\Framework\TestCase;

final class AccessRequestTest extends TestCase
{
    public function testGetters(): void
    {
        $accessRequest = new AccessRequest(
            '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            'Mathieu Marchois',
            'mathieu@fairness.coop',
            'Fairness',
            'password',
            true,
            '82050375300015',
            'Je souhaite créer un compte',
        );

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $accessRequest->getUuid());
        $this->assertSame('Je souhaite créer un compte', $accessRequest->getComment());
        $this->assertSame('mathieu@fairness.coop', $accessRequest->getEmail());
        $this->assertSame('Mathieu Marchois', $accessRequest->getFullName());
        $this->assertSame('Fairness', $accessRequest->getOrganization());
        $this->assertSame('82050375300015', $accessRequest->getSiret());
        $this->assertSame('password', $accessRequest->getPassword());
        $this->assertTrue($accessRequest->isConsentToBeContacted());
    }

    public function testSetters(): void
    {
        $accessRequest = new AccessRequest(
            '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            'Mathieu Marchois',
            'mathieu@fairness.coop',
            'Fairness',
            'password',
            true,
            '82050375300015',
            'Je souhaite créer un compte',
        );

        $accessRequest->setFullName('Marchois Mathieu');
        $accessRequest->setEmail('mathieu.marchois@fairness.coop');
        $accessRequest->setOrganization('Fairness scop');
        $accessRequest->setSiret('82050375300014');

        $this->assertSame('mathieu.marchois@fairness.coop', $accessRequest->getEmail());
        $this->assertSame('Marchois Mathieu', $accessRequest->getFullName());
        $this->assertSame('Fairness scop', $accessRequest->getOrganization());
        $this->assertSame('82050375300014', $accessRequest->getSiret());
    }
}
