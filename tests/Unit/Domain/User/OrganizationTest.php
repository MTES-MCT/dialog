<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use PHPUnit\Framework\TestCase;
use App\Domain\User\Organization;
use App\Domain\User\User;

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
        $this->assertEmpty($organization->getUsers());

        $user = $this->createMock(User::class);
        $organization->addUser($user);
        $organization->addUser($user); // Test deduplication of users

        $this->assertSame([$user], $organization->getUsers());
    }
}
