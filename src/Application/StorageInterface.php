<?php

declare(strict_types=1);

namespace App\Application;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface StorageInterface
{
    public function write(string $folder, UploadedFile $file): string;

    public function delete(string $path): void;

    public function getUrl(string $path): string;

    public function read(string $path): ?string;

    public function getMimeType(string $path): ?string;
}
