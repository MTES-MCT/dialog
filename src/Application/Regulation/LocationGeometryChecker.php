<?php

declare(strict_types=1);

namespace App\Application\Regulation;

use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\Exception\ZoneWithoutStreetsException;
use App\Application\Regulation\Command\Location\RoadCommandInterface;
use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Specification\CanOrganizationInterveneOnGeometry;

final class LocationGeometryChecker
{
    public function __construct(
        private CanOrganizationInterveneOnGeometry $canOrganizationInterveneOnGeometry,
    ) {
    }

    public function checkGeometry(RoadCommandInterface $roadCommand, string $organizationUuid, string $geometry): void
    {
        // Pour une zone, la géométrie enregistrée (les tronçons de rues couverts) peut être
        // vide si le périmètre ne contient aucune rue, et ST_Intersects avec une géométrie
        // vide renvoie toujours faux. La compétence de l'organisation s'évalue donc sur le
        // polygone dessiné, qui représente la zone d'intervention voulue.
        $competenceGeometry = $roadCommand instanceof SaveZoneCommand && $roadCommand->geometry
            ? $roadCommand->geometry
            : $geometry;

        if (!$this->canOrganizationInterveneOnGeometry->isSatisfiedBy($organizationUuid, $competenceGeometry)) {
            throw new OrganizationCannotInterveneOnGeometryException();
        }

        // Une zone sans rue n'aurait aucun linéaire exploitable (carte, exports DATEX II et
        // CIFS) : on refuse l'enregistrement pour que l'usager corrige son tracé.
        if ($roadCommand instanceof SaveZoneCommand && GeoJSON::isEmptyGeometryCollection($geometry)) {
            throw new ZoneWithoutStreetsException();
        }
    }

    public function checkExceptionGeometry(?string $roadType, ?string $geometry): void
    {
        // Comme pour une localisation « Tracé de zone » : une zone sans rue ne soustrairait rien.
        if ($roadType === RoadTypeEnum::ZONE->value
            && ($geometry === null || GeoJSON::isEmptyGeometryCollection($geometry))) {
            throw new ZoneWithoutStreetsException();
        }
    }
}
