<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;

/**
 * Calcule et contrôle la géométrie d'une localisation comme lors de son enregistrement,
 * sans rien persister. Renvoie la géométrie GeoJSON calculée, ou lève l'exception
 * correspondant au refus (géocodage, compétence de l'organisation, zone sans rue).
 */
final readonly class CheckLocationQuery implements QueryInterface
{
    public function __construct(
        public SaveLocationCommand $location,
    ) {
    }
}
