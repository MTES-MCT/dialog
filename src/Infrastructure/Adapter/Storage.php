<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\StorageInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class Storage implements StorageInterface
{
    public function __construct(
        private FilesystemOperator $storage,
        private string $mediaLocation,
    ) {
    }

    public function write(string $folder, UploadedFile $file): string
    {
        $path = \sprintf('%s/%s', $folder, $file->getClientOriginalName());
        $this->storage->write($path, $file->getContent(), [
            'visibility' => 'public',
            'directory_visibility' => 'public',
        ]);

        return $path;
    }

    public function delete(string $path): void
    {
        if (!$this->storage->has($path)) {
            return;
        }

        $this->storage->delete($path);
    }

    public function getUrl(string $path): string
    {
        return \sprintf('%s/%s', $this->mediaLocation, $path);
    }

    public function read(string $path): ?string
    {
        if (!$this->storage->has($path)) {
            return null;
        }

        return $this->storage->read($path);
    }

    public function getMimeType(string $path): ?string
    {
        if (!$this->storage->has($path)) {
            return null;
        }

        return $this->storage->mimeType($path);
    }
}
