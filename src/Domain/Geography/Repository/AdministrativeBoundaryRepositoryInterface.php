<?php

declare(strict_types=1);

namespace App\Domain\Geography\Repository;

use App\Application\Geography\View\AdministrativeBoundaryView;
use App\Application\Organization\View\MapBboxView;

interface AdministrativeBoundaryRepositoryInterface
{
    public function findOneByCode(string $codeType, string $code): ?AdministrativeBoundaryView;

    /** @return AdministrativeBoundaryView[] */
    public function findAllViews(): array;

    /**
     * Crée ou met à jour le contour d'une collectivité.
     *
     * @param string $geometry Contour au format GeoJSON (EPSG:4326)
     */
    public function save(string $codeType, string $code, string $name, string $geometry, \DateTimeInterface $updatedAt): void;

    public function delete(string $codeType, string $code): void;

    /**
     * Retourne la bbox du contour d'une collectivité, pour y centrer la carte.
     */
    public function findMapBbox(string $codeType, string $code): ?MapBboxView;
}
