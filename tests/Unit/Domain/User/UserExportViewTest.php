<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\UserExportView;
use PHPUnit\Framework\TestCase;

final class UserExportViewTest extends TestCase
{
    public function testConstructorWithVerifiedUser(): void
    {
        $registrationDate = new \DateTimeImmutable('2024-05-07T10:30:00+00:00');
        $lastActiveAt = new \DateTimeImmutable('2024-12-01T15:45:30+00:00');

        $userExportView = new UserExportView(
            fullName: 'John Doe',
            email: 'john.doe@example.com',
            registrationDate: $registrationDate,
            lastActiveAt: $lastActiveAt,
            organizationName: 'Acme Corp',
        );

        $this->assertSame('John Doe', $userExportView->fullName);
        $this->assertSame('john.doe@example.com', $userExportView->email);
        $this->assertSame('2024-05-07T10:30:00+00:00', $userExportView->registrationDate);
        $this->assertSame('2024-12-01T15:45:30+00:00', $userExportView->lastActiveAt);
        $this->assertSame('Acme Corp', $userExportView->organizationName);
    }

    public function testConstructorWithNullLastActiveAt(): void
    {
        $registrationDate = new \DateTime('2023-06-10T14:20:00+00:00');

        $userExportView = new UserExportView(
            fullName: 'Bob Wilson',
            email: 'bob.wilson@example.com',
            registrationDate: $registrationDate,
            lastActiveAt: null,
            organizationName: 'Global Inc',
        );

        $this->assertSame('Bob Wilson', $userExportView->fullName);
        $this->assertSame('bob.wilson@example.com', $userExportView->email);
        $this->assertSame('2023-06-10T14:20:00+00:00', $userExportView->registrationDate);
        $this->assertNull($userExportView->lastActiveAt);
        $this->assertSame('Global Inc', $userExportView->organizationName);
    }

    public function testDateTimeFormatConversionToAtomFormat(): void
    {
        $registrationDate = new \DateTime('2024-03-21T16:45:30', new \DateTimeZone('Europe/Paris'));
        $lastActiveAt = new \DateTimeImmutable('2024-10-15T09:15:00Z');

        $userExportView = new UserExportView(
            fullName: 'Alice Johnson',
            email: 'alice@example.com',
            registrationDate: $registrationDate,
            lastActiveAt: $lastActiveAt,
            organizationName: 'Innovation Lab',
        );

        $this->assertStringMatchesFormat('%d-%d-%dT%d:%d:%d%S', $userExportView->registrationDate);
        $this->assertStringMatchesFormat('%d-%d-%dT%d:%d:%d%S', $userExportView->lastActiveAt);
        $this->assertStringContainsString('T', $userExportView->registrationDate);
        $this->assertStringContainsString('T', $userExportView->lastActiveAt);
    }

    public function testReadonlyPropertyAccess(): void
    {
        $registrationDate = new \DateTimeImmutable('2024-07-01T12:00:00+00:00');

        $userExportView = new UserExportView(
            fullName: 'Test User',
            email: 'test@example.com',
            registrationDate: $registrationDate,
            lastActiveAt: null,
            organizationName: 'Test Organization',
        );

        $this->assertIsString($userExportView->fullName);
        $this->assertIsString($userExportView->email);
        $this->assertIsString($userExportView->registrationDate);
        $this->assertNull($userExportView->lastActiveAt);
        $this->assertIsString($userExportView->organizationName);
    }
}
