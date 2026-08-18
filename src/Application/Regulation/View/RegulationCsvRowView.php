<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

/**
 * Une ligne de l'export CSV de la base nationale des restrictions.
 *
 * Conformément à la spécification, une ligne = une emprise (location) : un arrêté
 * comportant plusieurs mesures, chacune portant plusieurs emprises, génère donc
 * autant de lignes que le total de ses emprises.
 */
final readonly class RegulationCsvRowView
{
    public function __construct(
        public string $regulationOrderUuid,
        public string $title,
        public string $category,
        public string $status,
        public ?\DateTimeInterface $startDate,
        public ?\DateTimeInterface $endDate,
        public string $organizationName,
        public string $linkPdf,
        public string $measureUuid,
        public string $measureType,
        public string $locationUuid,
        public string $locationType,
        public string $locationLabel,
    ) {
    }
}
