<?php

declare(strict_types=1);

namespace App\Domain\User;

class Organization
{
    private string $name;
    private ?string $siret;
    private ?string $logo;
    private ?string $code = null;
    private ?string $codeType = null;
    private ?string $departmentName = null;
    private ?string $departmentCode = null;
    private ?string $geometry;
    private \DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    public function __construct(
        private string $uuid,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
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

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function getDepartmentName(): ?string
    {
        return $this->departmentName;
    }

    public function setDepartmentName(?string $departmentName): self
    {
        $this->departmentName = $departmentName;

        return $this;
    }

    public function getDepartmentCode(): ?string
    {
        return $this->departmentCode;
    }

    public function setDepartmentCode(?string $departmentCode): self
    {
        $this->departmentCode = $departmentCode;

        return $this;
    }

    public function getDepartmentCodeWithName(): ?string
    {
        if (!$this->departmentCode || !$this->departmentName) {
            return null;
        }

        return \sprintf('%s (%s)', $this->getDepartmentName(), $this->getDepartmentCode());
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCodeType(): ?string
    {
        return $this->codeType;
    }

    public function setCodeType(string $codeType): self
    {
        $this->codeType = $codeType;

        return $this;
    }

    public function getGeometry(): ?string
    {
        return $this->geometry;
    }

    public function setGeometry(string $geometry): self
    {
        $this->geometry = $geometry;

        return $this;
    }

    public function getCodeWithType(): string
    {
        if (!$this->getCode() || !$this->getCodeType()) {
            return 'N/A';
        }

        return \sprintf('%s (%s)', $this->getCode(), $this->getCodeType());
    }

    public function update(string $name, string $siret): void
    {
        $this->name = $name;
        $this->siret = $siret;
    }
}
