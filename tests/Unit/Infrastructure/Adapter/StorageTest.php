<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\Storage;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

final class StorageTest extends TestCase
{
    public function testDelete(): void
    {
        $filesystemOperator = $this->createMock(FilesystemOperator::class);
        $filesystemOperator
            ->expects(self::once())
            ->method('has')
            ->with('logo.jpeg')
            ->willReturn(true);
        $filesystemOperator
            ->expects(self::once())
            ->method('delete')
            ->with('logo.jpeg');

        $storage = new Storage($filesystemOperator, '/path/to/medias');
        $storage->delete('logo.jpeg');
    }

    public function testGet(): void
    {
        $filesystemOperator = $this->createMock(FilesystemOperator::class);
        $storage = new Storage($filesystemOperator, '/path/to/medias');

        $this->assertSame('/path/to/medias/logo.jpeg', $storage->getUrl('logo.jpeg'));
    }

    public function testRead(): void
    {
        $filesystemOperator = $this->createMock(FilesystemOperator::class);
        $storage = new Storage($filesystemOperator, '/path/to/medias');

        $filesystemOperator
            ->expects(self::once())
            ->method('has')
            ->with('logo.jpeg')
            ->willReturn(true);

        $filesystemOperator
            ->expects(self::once())
            ->method('read')
            ->willReturn('logoResource');

        $this->assertSame('logoResource', $storage->read('logo.jpeg'));
    }

    public function testReadFileNotFound(): void
    {
        $filesystemOperator = $this->createMock(FilesystemOperator::class);
        $storage = new Storage($filesystemOperator, '/path/to/medias');

        $filesystemOperator
            ->expects(self::once())
            ->method('has')
            ->with('logo.jpeg')
            ->willReturn(false);

        $filesystemOperator
            ->expects(self::never())
            ->method('read');

        $this->assertNull($storage->read('logo.jpeg'));
    }

    public function testGetMimeType(): void
    {
        $filesystemOperator = $this->createMock(FilesystemOperator::class);
        $storage = new Storage($filesystemOperator, '/path/to/medias');

        $filesystemOperator
            ->expects(self::once())
            ->method('has')
            ->with('logo.jpeg')
            ->willReturn(true);

        $filesystemOperator
            ->expects(self::once())
            ->method('mimeType')
            ->willReturn('image/jpeg');

        $this->assertSame('image/jpeg', $storage->getMimeType('logo.jpeg'));
    }

    public function testGetMimeTypeFileNotFound(): void
    {
        $filesystemOperator = $this->createMock(FilesystemOperator::class);
        $storage = new Storage($filesystemOperator, '/path/to/medias');

        $filesystemOperator
            ->expects(self::once())
            ->method('has')
            ->with('logo.jpeg')
            ->willReturn(false);

        $filesystemOperator
            ->expects(self::never())
            ->method('mimeType');

        $this->assertNull($storage->getMimeType('logo.jpeg'));
    }

    public function testCantDelete(): void
    {
        $filesystemOperator = $this->createMock(FilesystemOperator::class);
        $filesystemOperator
            ->expects(self::once())
            ->method('has')
            ->with('logo.jpeg')
            ->willReturn(false);
        $filesystemOperator
            ->expects(self::never())
            ->method('delete');

        $storage = new Storage($filesystemOperator, '/path/to/medias');
        $storage->delete('logo.jpeg');
    }
}
