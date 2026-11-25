<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SaveMeasureCommandConstraintValidator extends ConstraintValidator
{
    // Sous-commande qui porte le sens, selon le type de voie de la localisation.
    // La ville entière et la zone n'ont pas de sens : elles ne sont pas concernées.
    private const DIRECTION_FIELD_BY_ROAD_TYPE = [
        RoadTypeEnum::LANE->value => 'namedStreet',
        RoadTypeEnum::DEPARTMENTAL_ROAD->value => 'departmentalRoad',
        RoadTypeEnum::NATIONAL_ROAD->value => 'nationalRoad',
        RoadTypeEnum::RAW_GEOJSON->value => 'rawGeoJSON',
    ];

    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveMeasureCommand) {
            throw new UnexpectedValueException($command, SaveMeasureCommand::class);
        }

        if ($command->type !== MeasureTypeEnum::ONE_WAY->value) {
            return;
        }

        // Un sens unique n'a de signification que dans un sens : « Double sens » est refusé
        foreach ($command->locations as $index => $location) {
            $field = self::DIRECTION_FIELD_BY_ROAD_TYPE[$location->roadType] ?? null;

            if ($field === null || $location->$field === null) {
                continue;
            }

            if ($location->$field->direction === DirectionEnum::BOTH->value) {
                $this->context->buildViolation('measure.locations.error.one_way_direction')
                    ->atPath(\sprintf('locations[%s].%s.direction', $index, $field))
                    ->addViolation();
            }
        }
    }
}
