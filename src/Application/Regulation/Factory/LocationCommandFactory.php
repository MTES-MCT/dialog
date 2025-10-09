<?php

declare(strict_types=1);

namespace App\Application\Regulation\Factory;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Infrastructure\DTO\Event\SaveLocationDTO;

final class LocationCommandFactory
{
    public function fromDto(SaveLocationDTO $dto): SaveLocationCommand
    {
        $cmd = new SaveLocationCommand();
        $cmd->roadType = $dto->roadType?->value;

        if ($dto->namedStreet) {
            $ns = new SaveNamedStreetCommand();
            $ns->cityCode = $dto->namedStreet->cityCode;
            $ns->cityLabel = $dto->namedStreet->cityLabel;
            $ns->roadBanId = $dto->namedStreet->roadBanId;
            $ns->roadName = $dto->namedStreet->roadName;
            $ns->fromPointType = $dto->namedStreet->fromPointType;
            $ns->fromHouseNumber = $dto->namedStreet->fromHouseNumber;
            $ns->fromRoadBanId = $dto->namedStreet->fromRoadBanId;
            $ns->fromRoadName = $dto->namedStreet->fromRoadName;
            $ns->toPointType = $dto->namedStreet->toPointType;
            $ns->toHouseNumber = $dto->namedStreet->toHouseNumber;
            $ns->toRoadBanId = $dto->namedStreet->toRoadBanId;
            $ns->toRoadName = $dto->namedStreet->toRoadName;
            $ns->geometry = $dto->namedStreet->geometry;
            if ($dto->namedStreet->direction) {
                $ns->direction = $dto->namedStreet->direction->value;
            }
            $cmd->namedStreet = $ns;
        }

        if ($dto->departmentalRoad) {
            $nr = new SaveNumberedRoadCommand();
            $nr->administrator = $dto->departmentalRoad->administrator;
            $nr->roadNumber = $dto->departmentalRoad->roadNumber;
            $nr->fromDepartmentCode = $dto->departmentalRoad->fromDepartmentCode;
            $nr->fromPointNumber = $dto->departmentalRoad->fromPointNumber;
            $nr->fromAbscissa = $dto->departmentalRoad->fromAbscissa;
            $nr->fromSide = $dto->departmentalRoad->fromSide;
            $nr->toDepartmentCode = $dto->departmentalRoad->toDepartmentCode;
            $nr->toPointNumber = $dto->departmentalRoad->toPointNumber;
            $nr->toAbscissa = $dto->departmentalRoad->toAbscissa;
            $nr->toSide = $dto->departmentalRoad->toSide;
            if ($dto->departmentalRoad->direction) {
                $nr->direction = $dto->departmentalRoad->direction->value;
            }
            $nr->geometry = $dto->departmentalRoad->geometry;
            $cmd->departmentalRoad = $nr;
        }

        if ($dto->nationalRoad) {
            $nr = new SaveNumberedRoadCommand();
            $nr->administrator = $dto->nationalRoad->administrator;
            $nr->roadNumber = $dto->nationalRoad->roadNumber;
            $nr->fromDepartmentCode = $dto->nationalRoad->fromDepartmentCode;
            $nr->fromPointNumber = $dto->nationalRoad->fromPointNumber;
            $nr->fromAbscissa = $dto->nationalRoad->fromAbscissa;
            $nr->fromSide = $dto->nationalRoad->fromSide;
            $nr->toDepartmentCode = $dto->nationalRoad->toDepartmentCode;
            $nr->toPointNumber = $dto->nationalRoad->toPointNumber;
            $nr->toAbscissa = $dto->nationalRoad->toAbscissa;
            $nr->toSide = $dto->nationalRoad->toSide;
            if ($dto->nationalRoad->direction) {
                $nr->direction = $dto->nationalRoad->direction->value;
            }
            $nr->geometry = $dto->nationalRoad->geometry;
            $cmd->nationalRoad = $nr;
        }

        if ($dto->rawGeoJSON) {
            $r = new SaveRawGeoJSONCommand();
            $r->label = $dto->rawGeoJSON->label;
            $r->geometry = $dto->rawGeoJSON->geometry;
            $cmd->rawGeoJSON = $r;
        }

        return $cmd;
    }
}
