<?php

declare(strict_types=1);

namespace App\Domain\User;

class News
{
    private \DateTimeInterface $createdAt;
    private string $name;
    private string $linkTitle;
    private string $link;
    private string $content;

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

    public function getLinkTitle(): string
    {
        return $this->linkTitle;
    }

    public function setLinkTitle(string $linkTitle): self
    {
        $this->linkTitle = $linkTitle;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

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
}
