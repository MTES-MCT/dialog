<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

/**
 * Requête d'export CSV de la base nationale des restrictions.
 *
 * Reprend les mêmes filtres que l'API JSON de recherche (voir
 * {@see GetRegulationOrdersForApiQuery}), mais sans pagination : l'export vise à
 * récupérer l'intégralité des lignes correspondant aux filtres.
 *
 * Le résultat est une liste de {@see \App\Application\Regulation\View\RegulationCsvRowView}
 * où une ligne correspond à une emprise (location).
 *
 * Par défaut (aucun filtre), l'export porte sur toute la base publiée
 * (`vigueurStatus = all`), car le principal usage est le téléchargement complet.
 */
final readonly class GetRegulationOrdersForCsvExportQuery implements QueryInterface
{
    public function __construct(
        public string $vigueurStatus = GetRegulationOrdersForApiQuery::STATUS_ALL,
        public ?string $inseeCode = null,
        public ?\DateTimeInterface $dateStart = null,
        public ?\DateTimeInterface $dateEnd = null,
        public ?string $category = null,
        public ?string $measureType = null,
        public bool $includeHeavyGoodsVehicle = true,
    ) {
    }
}
