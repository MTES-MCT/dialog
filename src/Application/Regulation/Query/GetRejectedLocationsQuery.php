<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

final readonly class GetRejectedLocationsQuery implements QueryInterface
{
    /**
     * Sans $regulationOrderRecordUuid, retourne les localisations rejetées de tous les arrêtés de l'organisation.
     */
    public function __construct(
        public string $organizationUuid,
        public ?string $regulationOrderRecordUuid = null,
    ) {
    }
}
