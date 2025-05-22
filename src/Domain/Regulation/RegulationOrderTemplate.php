<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\User\Organization;

class RegulationOrderTemplate
{
    private string $name;
    private string $title;
    private string $visaContent;
    private string $consideringContent;
    private string $articleContent;
    private \DateTimeInterface $createdAt;
    private ?Organization $organization = null;

    public function __construct(
        private string $uuid,
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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getVisaContent(): string
    {
        return $this->visaContent;
    }

    public function setVisaContent(string $visaContent): self
    {
        $this->visaContent = $visaContent;

        return $this;
    }

    public function getConsideringContent(): string
    {
        return $this->consideringContent;
    }

    public function setConsideringContent(string $consideringContent): self
    {
        $this->consideringContent = $consideringContent;

        return $this;
    }

    public function getArticleContent(): string
    {
        return $this->articleContent;
    }

    public function setArticleContent(string $articleContent): self
    {
        $this->articleContent = $articleContent;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
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
