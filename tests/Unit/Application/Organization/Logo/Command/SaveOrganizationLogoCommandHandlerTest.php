<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\Logo\Command;

use App\Application\Organization\Logo\Command\SaveOrganizationLogoCommand;
use App\Application\Organization\Logo\Command\SaveOrganizationLogoCommandHandler;
use App\Application\StorageInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SaveOrganizationLogoCommandHandlerTest extends TestCase
{
    private MockObject $storage;

    public function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
    }

    public function testSave(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getLogo')
            ->willReturn('/path/to/logo.png');
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('496bd752-c217-4625-ba0c-7454dc218516');

        $this->storage
            ->expects(self::once())
            ->method('delete')
            ->with('/path/to/logo.png');

        $this->storage
            ->expects(self::once())
            ->method('write')
            ->with('organizations/496bd752-c217-4625-ba0c-7454dc218516', $file)
            ->willReturn('organizations/496bd752-c217-4625-ba0c-7454dc218516/logo.png');

        $organization
            ->expects(self::once())
            ->method('setLogo')
            ->with('organizations/496bd752-c217-4625-ba0c-7454dc218516/logo.png');

        $handler = new SaveOrganizationLogoCommandHandler(
            $this->storage,
        );
        $command = new SaveOrganizationLogoCommand($organization);
        $command->file = $file;

        $handler($command);
    }
}
