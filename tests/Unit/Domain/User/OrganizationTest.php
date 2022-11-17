<?php

declare(strict_types=1);

namespace App\Tests\Domain\User;

use PHPUnit\Framework\TestCase;
use App\Domain\User\Organization;

final class OrganizationTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = new Organization(
            '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            'Mairie de Savenay',
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $organization->getUuid());
        $this->assertSame('Mairie de Savenay', $organization->getName());
    }
}
