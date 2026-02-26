<?php

declare(strict_types=1);

namespace App\Domain\User;

class ReportAddress
{
    private ?\DateTimeInterface $createdAt = null;

    public function __construct(
        private string $uuid,
        private string $content,
        private string $location,
        private User $user,
        private bool $hasBeenContacted = false,
        private ?string $ignReportId = null,
        private ?string $ignReportStatus = null,
        private ?\DateTimeInterface $ignStatusUpdatedAt = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getUser(): User
    {
        return $this->user;
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

    public function getHasBeenContacted(): bool
    {
        return $this->hasBeenContacted;
    }

    public function setHasBeenContacted(bool $hasBeenContacted): self
    {
        $this->hasBeenContacted = $hasBeenContacted;

        return $this;
    }

    public function getIgnReportId(): ?string
    {
        return $this->ignReportId;
    }

    public function setIgnReportId(?string $ignReportId): self
    {
        $this->ignReportId = $ignReportId;

        return $this;
    }

    public function getIgnReportStatus(): ?string
    {
        return $this->ignReportStatus;
    }

    public function setIgnReportStatus(?string $ignReportStatus): self
    {
        $this->ignReportStatus = $ignReportStatus;

        return $this;
    }

    public function getIgnStatusUpdatedAt(): ?\DateTimeInterface
    {
        return $this->ignStatusUpdatedAt;
    }

    public function setIgnStatusUpdatedAt(?\DateTimeInterface $ignStatusUpdatedAt): self
    {
        $this->ignStatusUpdatedAt = $ignStatusUpdatedAt;

        return $this;
    }
}
