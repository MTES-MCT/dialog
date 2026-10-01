<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Validation;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Validator\SaveWholeCityExceptionCommandConstraint;
use App\Infrastructure\Validator\SaveWholeCityExceptionCommandConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SaveWholeCityExceptionCommandConstraintValidatorTest extends ConstraintValidatorTestCase
{
    private $constraintObj;

    protected function setUp(): void
    {
        parent::setUp();
        $this->constraintObj = new SaveWholeCityExceptionCommandConstraint();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new SaveWholeCityExceptionCommandConstraintValidator();
    }

    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate('not a command instance', $this->constraintObj);
    }

    public function testLaneExceptionIsNotConcerned(): void
    {
        $command = new SaveWholeCityExceptionCommand();

        $this->validator->validate($command, $this->constraintObj);
        $this->assertNoViolation();
    }

    public function testValidDepartmentalRoadException(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $command->departmentalRoad = new SaveNumberedRoadCommand();
        $command->departmentalRoad->administrator = 'Ardèche';
        $command->departmentalRoad->roadNumber = 'D110';
        $command->departmentalRoad->fromPointNumberWithDepartmentCode = '07##6';
        $command->departmentalRoad->toPointNumberWithDepartmentCode = '07##15';

        $this->validator->validate($command, $this->constraintObj);
        $this->assertNoViolation();
    }

    public function testViolationsWhenNumberedRoadFieldsAreBlank(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::NATIONAL_ROAD->value;
        $command->nationalRoad = new SaveNumberedRoadCommand();

        $this->validator->validate($command, $this->constraintObj);

        $this->buildViolation('common.error.not_blank')
            ->atPath('property.path.nationalRoad.administrator')
            ->buildNextViolation('common.error.not_blank')
            ->atPath('property.path.nationalRoad.roadNumber')
            ->buildNextViolation('regulation.location.pointNumber.error.blank')
            ->atPath('property.path.nationalRoad.fromPointNumber')
            ->buildNextViolation('regulation.location.pointNumber.error.blank')
            ->atPath('property.path.nationalRoad.toPointNumber')
            ->assertRaised();
    }

    public function testZeroIsAFilledValue(): void
    {
        // empty('0') vaut true en PHP : le contrôle « requis » ne doit pas rejeter « 0 ».
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $command->departmentalRoad = new SaveNumberedRoadCommand();
        $command->departmentalRoad->administrator = '0';
        $command->departmentalRoad->roadNumber = '0';
        $command->departmentalRoad->fromPointNumberWithDepartmentCode = '0';
        $command->departmentalRoad->toPointNumberWithDepartmentCode = '0';

        $this->validator->validate($command, $this->constraintObj);
        $this->assertNoViolation();
    }

    public function testViolationWhenSubCommandIsMissing(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;

        $this->validator->validate($command, $this->constraintObj);

        $this->buildViolation('common.error.not_blank')
            ->atPath('property.path.departmentalRoad')
            ->assertRaised();
    }
}
