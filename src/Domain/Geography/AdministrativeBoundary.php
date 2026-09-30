<?php

declare(strict_types=1);

namespace App\Domain\Geography;

/**
 * Contour officiel d'une collectivité (commune, EPCI, département ou région), identifiée par
 * son type et son code du COG (code officiel géographique).
 *
 * Sert à filtrer les restrictions par collectivité : une emprise concerne une collectivité
 * lorsque sa géométrie intersecte ce contour.
 */
class AdministrativeBoundary
{
    public function __construct(
        private string $codeType,
        private string $code,
        private string $name,
        private string $geometry,
        private \DateTimeInterface $updatedAt,
    ) {
    }

    public function getCodeType(): string
    {
        return $this->codeType;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGeometry(): string
    {
        return $this->geometry;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
