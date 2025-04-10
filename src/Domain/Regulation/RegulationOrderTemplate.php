<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\User\Organization;

class RegulationOrderTemplate
{
    public function __construct(
        private string $uuid,
        private string $name,
        private string $title,
        private string $visaContent,
        private string $consideringContent,
        private string $articleContent,
        private \DateTimeInterface $createdAt,
        private ?Organization $organization = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getVisaContent(): string
    {
        return $this->visaContent;
    }

    public function getConsideringContent(): string
    {
        return $this->consideringContent;
    }

    public function getArticleContent(): string
    {
        return $this->articleContent;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function update(
        string $name,
        string $title,
        string $visaContent,
        string $consideringContent,
        string $articleContent,
    ): void {
        $this->name = $name;
        $this->title = $title;
        $this->visaContent = $visaContent;
        $this->consideringContent = $consideringContent;
        $this->articleContent = $articleContent;
    }
}
