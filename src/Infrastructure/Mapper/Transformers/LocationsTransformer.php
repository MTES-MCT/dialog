<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper\Transformers;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Infrastructure\DTO\Event\SaveLocationDTO;
use App\Infrastructure\DTO\Event\SaveNamedStreetDTO;
use App\Infrastructure\DTO\Event\SaveNumberedRoadDTO;
use App\Infrastructure\DTO\Event\SaveRawGeoJSONDTO;
use App\Infrastructure\DTO\Event\SaveWholeCityDTO;
use App\Infrastructure\DTO\Event\SaveWholeCityExceptionDTO;
use App\Infrastructure\DTO\Event\SaveZoneDTO;

final class LocationsTransformer
{
    public static function toCommands(array $locationDtos): array
    {
        $commands = [];

        foreach ($locationDtos as $dto) {
            if (!$dto instanceof SaveLocationDTO) {
                continue;
            }

            $cmd = new SaveLocationCommand();
            $cmd->roadType = $dto->roadType?->value;

            if ($dto->namedStreet) {
                $cmd->namedStreet = self::buildNamedStreet($dto->namedStreet, $cmd->roadType);
            }

            if ($dto->departmentalRoad) {
                $cmd->departmentalRoad = self::buildNumberedRoad($dto->departmentalRoad, $cmd->roadType);
            }

            if ($dto->nationalRoad) {
                $cmd->nationalRoad = self::buildNumberedRoad($dto->nationalRoad, $cmd->roadType);
            }

            if ($dto->rawGeoJSON) {
                $cmd->rawGeoJSON = self::buildRawGeoJSON($dto->rawGeoJSON, $cmd->roadType);
                $cmd->rawGeoJSON->exceptions = self::buildExceptions($dto->rawGeoJSON->exceptions);
            }

            if ($dto->zone) {
                $cmd->zone = self::buildZone($dto->zone, $cmd->roadType);
                $cmd->zone->exceptions = self::buildExceptions($dto->zone->exceptions);
            }

            if ($dto->wholeCity) {
                $cmd->wholeCity = self::buildWholeCity($dto->wholeCity, $cmd->roadType);
            }

            $commands[] = $cmd;
        }

        return $commands;
    }

    private static function buildWholeCity(SaveWholeCityDTO $dto, ?string $roadType): SaveWholeCityCommand
    {
        $wc = new SaveWholeCityCommand();
        $wc->roadType = $roadType;
        $wc->cityCode = $dto->cityCode;
        $wc->cityLabel = $dto->cityLabel;
        $wc->exceptions = self::buildExceptions($dto->exceptions, $dto);

        return $wc;
    }

    /**
     * @param SaveWholeCityExceptionDTO[] $exceptionDtos
     *
     * @return SaveWholeCityExceptionCommand[]
     */
    private static function buildExceptions(array $exceptionDtos, ?SaveWholeCityDTO $hostCity = null): array
    {
        $commands = [];

        foreach ($exceptionDtos as $dto) {
            $exception = new SaveWholeCityExceptionCommand();
            $exception->roadType = $dto->roadType?->value;

            if ($dto->namedStreet) {
                $exception->namedStreet = self::buildNamedStreet($dto->namedStreet, $exception->roadType);

                // Exception d'une « Ville entière » : la ville est héritée de la localisation
                // parente si elle n'est pas renseignée (même comportement que le formulaire).
                if ($hostCity) {
                    $exception->namedStreet->cityCode ??= $hostCity->cityCode;
                    $exception->namedStreet->cityLabel ??= $hostCity->cityLabel;
                }
            }

            if ($dto->departmentalRoad) {
                $exception->departmentalRoad = self::buildNumberedRoad($dto->departmentalRoad, $exception->roadType);
            }

            if ($dto->nationalRoad) {
                $exception->nationalRoad = self::buildNumberedRoad($dto->nationalRoad, $exception->roadType);
            }

            if ($dto->zone) {
                // Pas d'exceptions imbriquées : le sous-objet zone d'une exception est pris sans les siennes.
                $exception->zone = self::buildZone($dto->zone, $exception->roadType);
            }

            if ($dto->rawGeoJSON) {
                $exception->rawGeoJSON = self::buildRawGeoJSON($dto->rawGeoJSON, $exception->roadType);
            }

            $commands[] = $exception;
        }

        return $commands;
    }

    private static function buildNamedStreet(SaveNamedStreetDTO $dto, ?string $roadType): SaveNamedStreetCommand
    {
        $ns = new SaveNamedStreetCommand();
        $ns->roadType = $roadType;
        $ns->cityCode = $dto->cityCode;
        $ns->cityLabel = $dto->cityLabel;
        $ns->roadName = $dto->roadName;
        $ns->fromPointType = $dto->fromPointType;
        $ns->fromHouseNumber = $dto->fromHouseNumber;
        $ns->fromRoadName = $dto->fromRoadName;
        $ns->toPointType = $dto->toPointType;
        $ns->toHouseNumber = $dto->toHouseNumber;
        $ns->toRoadName = $dto->toRoadName;
        $ns->geometry = $dto->geometry;
        if ($dto->direction) {
            $ns->direction = $dto->direction->value;
        }

        return $ns;
    }

    private static function buildNumberedRoad(SaveNumberedRoadDTO $dto, ?string $roadType): SaveNumberedRoadCommand
    {
        $nr = new SaveNumberedRoadCommand();
        $nr->roadType = $roadType;
        $nr->administrator = $dto->administrator;
        $nr->roadNumber = $dto->roadNumber;
        $nr->fromDepartmentCode = $dto->fromDepartmentCode;
        $nr->fromPointNumber = $dto->fromPointNumber;
        $nr->fromAbscissa = $dto->fromAbscissa;
        $nr->fromSide = $dto->fromSide;
        $nr->toDepartmentCode = $dto->toDepartmentCode;
        $nr->toPointNumber = $dto->toPointNumber;
        $nr->toAbscissa = $dto->toAbscissa;
        $nr->toSide = $dto->toSide;
        $nr->fromPointNumberWithDepartmentCode = SaveNumberedRoadCommand::encodePointNumberWithDepartmentCode($dto->fromDepartmentCode, $dto->fromPointNumber);
        $nr->toPointNumberWithDepartmentCode = SaveNumberedRoadCommand::encodePointNumberWithDepartmentCode($dto->toDepartmentCode, $dto->toPointNumber);
        if ($dto->direction) {
            $nr->direction = $dto->direction->value;
        }
        $nr->geometry = $dto->geometry;

        return $nr;
    }

    private static function buildRawGeoJSON(SaveRawGeoJSONDTO $dto, ?string $roadType): SaveRawGeoJSONCommand
    {
        $r = new SaveRawGeoJSONCommand();
        $r->roadType = $roadType;
        $r->label = $dto->label;
        $r->geometry = $dto->geometry;

        return $r;
    }

    private static function buildZone(SaveZoneDTO $dto, ?string $roadType): SaveZoneCommand
    {
        $z = new SaveZoneCommand();
        $z->roadType = $roadType;
        $z->label = $dto->label;
        $z->geometry = $dto->geometry;

        return $z;
    }
}
