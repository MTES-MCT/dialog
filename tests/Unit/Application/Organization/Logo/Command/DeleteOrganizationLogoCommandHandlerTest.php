<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\Logo\Command;

use App\Application\Organization\Logo\Command\DeleteOrganizationLogoCommand;
use App\Application\Organization\Logo\Command\DeleteOrganizationLogoCommandHandler;
use App\Application\StorageInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteOrganizationLogoCommandHandlerTest extends TestCase
{
    private MockObject $storage;

    public function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
    }

    public function testDelete(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getLogo')
            ->willReturn('/path/to/logo.png');

        $this->storage
            ->expects(self::once())
            ->method('delete')
            ->with('/path/to/logo.png');

        $organization
            ->expects(self::once())
            ->method('setLogo')
            ->with(null);

        $handler = new DeleteOrganizationLogoCommandHandler(
            $this->storage,
        );
        $command = new DeleteOrganizationLogoCommand($organization);

        $handler($command);
    }

    public function testWithoutFile(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getLogo')
            ->willReturn(null);

        $this->storage
            ->expects(self::never())
            ->method('delete');

        $organization
            ->expects(self::never())
            ->method('setLogo');

        $handler = new DeleteOrganizationLogoCommandHandler(
            $this->storage,
        );
        $command = new DeleteOrganizationLogoCommand($organization);

        $handler($command);
    }
}
