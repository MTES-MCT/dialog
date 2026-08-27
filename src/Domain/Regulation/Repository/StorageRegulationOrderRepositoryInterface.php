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

    public function remove(StorageRegulationOrder $storageRegulationOrder): void;
}
