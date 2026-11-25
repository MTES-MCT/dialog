<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Validator;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Validator\SaveMeasureCommandConstraint;
use App\Infrastructure\Validator\SaveMeasureCommandConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class SaveMeasureCommandConstraintValidatorTest extends ConstraintValidatorTestCase
{
    private const MESSAGE = 'measure.locations.error.one_way_direction';

    protected function createValidator(): SaveMeasureCommandConstraintValidator
    {
        return new SaveMeasureCommandConstraintValidator();
    }

    private function location(string $roadType, string $field, object $roadCommand): SaveLocationCommand
    {
        $location = new SaveLocationCommand();
        $location->roadType = $roadType;
        $location->$field = $roadCommand;

        return $location;
    }

    private function namedStreet(string $direction): SaveLocationCommand
    {
        $namedStreet = new SaveNamedStreetCommand();
        $namedStreet->direction = $direction;

        return $this->location(RoadTypeEnum::LANE->value, 'namedStreet', $namedStreet);
    }

    private function numberedRoad(string $roadType, string $direction): SaveLocationCommand
    {
        $numberedRoad = new SaveNumberedRoadCommand();
        $numberedRoad->direction = $direction;

        return $this->location($roadType, $roadType, $numberedRoad);
    }

    private function rawGeoJSON(string $direction): SaveLocationCommand
    {
        $rawGeoJSON = new SaveRawGeoJSONCommand();
        $rawGeoJSON->direction = $direction;

        return $this->location(RoadTypeEnum::RAW_GEOJSON->value, 'rawGeoJSON', $rawGeoJSON);
    }

    private function measure(string $type, SaveLocationCommand ...$locations): SaveMeasureCommand
    {
        $command = new SaveMeasureCommand();
        $command->type = $type;
        $command->locations = $locations;

        return $command;
    }

    public function testOneWayWithADirectionIsValid(): void
    {
        $this->validator->validate(
            $this->measure(
                MeasureTypeEnum::ONE_WAY->value,
                $this->namedStreet(DirectionEnum::A_TO_B->value),
                $this->numberedRoad(RoadTypeEnum::DEPARTMENTAL_ROAD->value, DirectionEnum::B_TO_A->value),
                $this->rawGeoJSON(DirectionEnum::A_TO_B->value),
            ),
            new SaveMeasureCommandConstraint(),
        );

        $this->assertNoViolation();
    }

    public function testOneWayRefusesBothDirectionsOnEveryKindOfRoad(): void
    {
        $this->validator->validate(
            $this->measure(
                MeasureTypeEnum::ONE_WAY->value,
                $this->namedStreet(DirectionEnum::BOTH->value),
                $this->namedStreet(DirectionEnum::A_TO_B->value),
                $this->numberedRoad(RoadTypeEnum::DEPARTMENTAL_ROAD->value, DirectionEnum::BOTH->value),
                $this->numberedRoad(RoadTypeEnum::NATIONAL_ROAD->value, DirectionEnum::BOTH->value),
                $this->rawGeoJSON(DirectionEnum::BOTH->value),
            ),
            new SaveMeasureCommandConstraint(),
        );

        $this->buildViolation(self::MESSAGE)->atPath('property.path.locations[0].namedStreet.direction')
            ->buildNextViolation(self::MESSAGE)->atPath('property.path.locations[2].departmentalRoad.direction')
            ->buildNextViolation(self::MESSAGE)->atPath('property.path.locations[3].nationalRoad.direction')
            ->buildNextViolation(self::MESSAGE)->atPath('property.path.locations[4].rawGeoJSON.direction')
            ->assertRaised();
    }

    public function testOtherMeasureTypesAcceptBothDirections(): void
    {
        foreach (MeasureTypeEnum::cases() as $type) {
            if ($type === MeasureTypeEnum::ONE_WAY) {
                continue;
            }

            $this->validator->validate(
                $this->measure($type->value, $this->namedStreet(DirectionEnum::BOTH->value)),
                new SaveMeasureCommandConstraint(),
            );
        }

        $this->assertNoViolation();
    }

    public function testLocationsWithoutDirectionAreIgnored(): void
    {
        // Ville entière : pas de sens. Localisation incomplète : les autres contraintes s'en chargent.
        $incomplete = new SaveLocationCommand();
        $incomplete->roadType = RoadTypeEnum::LANE->value;

        $this->validator->validate(
            $this->measure(
                MeasureTypeEnum::ONE_WAY->value,
                $this->location(RoadTypeEnum::WHOLE_CITY->value, 'wholeCity', new SaveWholeCityCommand()),
                $incomplete,
            ),
            new SaveMeasureCommandConstraint(),
        );

        $this->assertNoViolation();
    }

    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(new \stdClass(), new SaveMeasureCommandConstraint());
    }
}
