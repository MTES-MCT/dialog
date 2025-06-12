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

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function update(?string $path, ?string $url): void
    {
        $this->path = $path;
        $this->url = $url;
    }
}
