<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\reportAddress;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class ReportAddressTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $user = $this->createMock(User::class);
        $date = new \DateTimeImmutable('2023-01-01 00:00:00');
        $reportAddress = new reportAddress('9cebe00d-04d8-48da-89b1-059f6b7bfe44', 'Ceci est un signalement', 'lane', $user);
        $reportAddress->setCreatedAt($date);

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $reportAddress->getUuid());
        $this->assertSame($user, $reportAddress->getUser());
        $this->assertSame('Ceci est un signalement', $reportAddress->getContent());
        $this->assertSame('lane', $reportAddress->getLocation());
        $this->assertSame($date, $reportAddress->getCreatedAt());
        $this->assertFalse($reportAddress->getHasBeenContacted());

        $reportAddress->setHasBeenContacted(true);
        $this->assertTrue($reportAddress->getHasBeenContacted());
    }
}
