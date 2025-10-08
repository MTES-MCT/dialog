<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class StorageRegulationOrder
{
    public function __construct(
        private string $uuid,
        private RegulationOrder $regulationOrder,
        private ?string $path,
        private ?string $url,
        private ?string $title = null,
        private ?int $fileSize = null,
        private ?string $mimeType = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize ? (int) round($this->fileSize / 1000) : null;
    }

    public function getMimeType(): ?string
    {
        switch ($this->mimeType) {
            case 'image/jpeg':
                return 'JPG';
            case 'application/pdf':
                return 'PDF';
            case 'application/msword':
                return 'DOC';
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return 'DOCX';
            case 'application/vnd.oasis.opendocument.text':
                return 'ODT';
            default:
                return $this->mimeType;
        }
    }

    public function update(
        ?string $path,
        ?string $url,
        ?string $title = null,
        ?int $fileSize = null,
        ?string $mimeType = null,
    ): void {
        $this->path = $path;
        $this->url = $url;
        $this->title = $title;
        $this->fileSize = $fileSize;
        $this->mimeType = $mimeType;
    }
}
