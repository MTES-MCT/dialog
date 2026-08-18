<?php

declare(strict_types=1);

namespace App\Application\Regulation;

use App\Application\Regulation\View\RegulationCsvRowView;

interface RegulationExportCsvGeneratorInterface
{
    /**
     * (Re)génère l'export CSV complet de la base et le stocke pour un téléchargement rapide.
     * Destiné à être appelé de manière planifiée (cron nocturne).
     */
    public function generate(): void;

    /**
     * Retourne le CSV complet mis en cache (le génère à la volée s'il n'existe pas encore).
     */
    public function getCachedCsv(): string;

    public function getCachedCsvSize(): int;

    /**
     * Écrit les lignes fournies au format CSV dans le flux donné.
     *
     * @param iterable<RegulationCsvRowView> $rows
     * @param resource                       $handle
     */
    public function writeCsv(iterable $rows, $handle): void;
}
