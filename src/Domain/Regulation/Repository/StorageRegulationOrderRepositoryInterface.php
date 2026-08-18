<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\StorageRegulationOrder;

interface StorageRegulationOrderRepositoryInterface
{
    public function add(StorageRegulationOrder $storageRegulationOrder): StorageRegulationOrder;

    public function findOneByRegulationOrderUuid(string $uuid): ?StorageRegulationOrder;

    /**
     * Retourne le chemin du fichier téléversé et l'URL externe du document associé
     * à chaque enregistrement d'arrêté, sous la forme $recordUuid => ['path' => ?string, 'url' => ?string].
     */
    public function getStoragesByRegulationOrderRecordUuids(array $uuids): array;

    /**
     * Retourne, pour chaque arrêté demandé, le chemin de stockage et l'URL externe
     * de son document source (le cas échéant), indexés par UUID de regulation order.
     *
     * @param string[] $regulationOrderUuids
     *
     * @return array<string, array{path: ?string, url: ?string}>
     */
    public function findPdfInfoByRegulationOrderUuids(array $regulationOrderUuids): array;

    public function remove(StorageRegulationOrder $storageRegulationOrder): void;
}
