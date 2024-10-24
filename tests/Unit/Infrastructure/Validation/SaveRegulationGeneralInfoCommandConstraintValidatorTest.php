<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Validation;

use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use App\Domain\User\Specification\DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier;
use App\Infrastructure\Validator\SaveRegulationGeneralInfoCommandConstraint;
use App\Infrastructure\Validator\SaveRegulationGeneralInfoCommandConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SaveRegulationGeneralInfoCommandConstraintValidatorTest extends ConstraintValidatorTestCase
{
    private $constraintObj;
    private $regulationOrderRecord;
    private $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->constraintObj = new SaveRegulationGeneralInfoCommandConstraint();
        $this->organization = $this->createMock(Organization::class);
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new SaveRegulationGeneralInfoCommandConstraintValidator(
            $this->createMock(DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier::class),
        );
    }

    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate('not a command instance', $this->constraintObj);
    }

    public function testValid(): void
    {
        $command = new SaveRegulationGeneralInfoCommand($this->regulationOrderRecord);
        $command->identifier = 'F01/2023';
        $command->organization = $this->organization;

        $this->validator->validate($command, $this->constraintObj);
        $this->assertNoViolation();
    }
}
