<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Geography\AdministrativeBoundaryResolver;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use Symfony\Component\HttpFoundation\Request;

/**
 * Paramètres de requête permettant de filtrer par collectivité, communs à l'API publique
 * et à l'URL de la carte (et donc au code d'intégration de l'iframe).
 */
final class AdministrativeBoundaryQueryParameters
{
    // Nom du paramètre portant le code du COG, pour chaque type de collectivité.
    public const NAMES = [
        OrganizationCodeTypeEnum::INSEE->value => 'inseeCode',
        OrganizationCodeTypeEnum::EPCI->value => 'epciCode',
        OrganizationCodeTypeEnum::DEPARTMENT->value => 'departmentCode',
        OrganizationCodeTypeEnum::REGION->value => 'regionCode',
    ];

    /**
     * Extrait de la requête les codes de collectivités demandés.
     *
     * @return array<string, string> Codes indexés par type (valeur de OrganizationCodeTypeEnum)
     */
    public static function fromRequest(Request $request): array
    {
        $codes = [];

        foreach (self::NAMES as $codeType => $name) {
            $code = AdministrativeBoundaryResolver::normalizeCode($request->query->getString($name));

            if ($code !== '') {
                $codes[$codeType] = $code;
            }
        }

        return $codes;
    }
}
