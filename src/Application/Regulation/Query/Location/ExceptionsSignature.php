<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\WholeCityException;

/**
 * Signatures comparables des exceptions d'une localisation, pour éviter de recalculer
 * la géométrie (géocodage, soustraction) quand rien n'a changé entre le formulaire
 * et l'état persisté.
 */
final class ExceptionsSignature
{
    public static function ofCommand(SaveWholeCityExceptionCommand $exception): string
    {
        return json_encode([$exception->roadType, $exception->toData()]);
    }

    public static function ofException(WholeCityException $exception): string
    {
        return json_encode([$exception->getRoadType(), $exception->getData()]);
    }

    /**
     * @param SaveWholeCityExceptionCommand[] $exceptions
     */
    public static function ofCommands(array $exceptions): array
    {
        $signature = array_map(self::ofCommand(...), $exceptions);
        sort($signature);

        return $signature;
    }

    public static function ofLocation(Location $location): array
    {
        $signature = array_map(self::ofException(...), $location->getExceptions());
        sort($signature);

        return $signature;
    }
}
