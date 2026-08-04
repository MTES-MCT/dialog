<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\User\Command\SaveReportAddressCommand;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveReportAddressCommandTest extends TestCase
{
    private MockObject $user;

    public function setUp(): void
    {
        $this->user = $this->createMock(User::class);
    }

    public function testStoresProvidedValues(): void
    {
        $command = new SaveReportAddressCommand(
            $this->user,
            location: 'Route départementale - D12',
            roadBanId: 'road-ban-id-123',
            organizationUuid: 'org-uuid',
        );

        self::assertSame($this->user, $command->user);
        self::assertSame('Route départementale - D12', $command->location);
        self::assertSame('road-ban-id-123', $command->roadBanId);
        self::assertSame('org-uuid', $command->organizationUuid);
        self::assertNull($command->content);
    }

    public function testDefaultsToNull(): void
    {
        $command = new SaveReportAddressCommand($this->user);

        self::assertNull($command->location);
        self::assertNull($command->roadBanId);
        self::assertNull($command->organizationUuid);
        self::assertNull($command->content);
    }
}
