<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class OrganizationTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = (new Organization('6598fd41-85cb-42a6-9693-1bc45f4dd392'))
            ->setName('Mairie de Savenay')
            ->setSiret('21440195200129');

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $organization->getUuid());
        $this->assertSame('Mairie de Savenay', $organization->getName());
        $this->assertSame('21440195200129', $organization->getSiret());
        $this->assertSame('Mairie de Savenay', (string) $organization);
    }
}
