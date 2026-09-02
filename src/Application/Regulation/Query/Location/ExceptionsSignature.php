<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Domain\Regulation\Location\Location;

/**
 * Signatures comparables des exceptions d'une localisation, pour éviter de recalculer
 * la géométrie (géocodage, soustraction) quand rien n'a changé entre le formulaire
 * et l'état persisté.
 */
final class ExceptionsSignature
{
    /**
     * @param SaveWholeCityExceptionCommand[] $exceptions
     */
    public static function ofCommands(array $exceptions): array
    {
        $signature = array_map(
            fn (SaveWholeCityExceptionCommand $exception) => json_encode([$exception->roadType, $exception->toData()]),
            $exceptions,
        );
        sort($signature);

        return $signature;
    }

    public static function ofLocation(Location $location): array
    {
        $signature = array_map(
            fn ($exception) => json_encode([$exception->getRoadType(), $exception->getData()]),
            $location->getExceptions(),
        );
        sort($signature);

        return $signature;
    }
}
