<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Validation;

use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Validator\SaveRegulationGeneralInfoCommandConstraint;
use App\Infrastructure\Validator\SaveRegulationGeneralInfoCommandConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SaveRegulationGeneralInfoCommandConstraintValidatorTest extends ConstraintValidatorTestCase
{
    private $constraintObj;
    private $organization;
    private $regulationOrderRecord;

    protected function setUp(): void
    {
        $this->defaultTimezone = 'UTC';
        parent::setUp();
        $this->constraintObj = new SaveRegulationGeneralInfoCommandConstraint();
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new SaveRegulationGeneralInfoCommandConstraintValidator(clientTimezone: 'Europe/Paris');
    }

    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate('not a command instance', $this->constraintObj);
    }

    public function provideValidCases(): array
    {
        return [
            [
                // Start date only
                'startDate' => '2023-03-12',
                'endDate' => '',
            ],
            [
                // End date after start date
                'startDate' => '2023-03-12',
                'endDate' => '2023-03-13',
            ],
            [
                // Same day
                'startDate' => '2023-03-12',
                'endDate' => '2023-03-12',
            ],
        ];
    }

    /**
     * @dataProvider provideValidCases
     */
    public function testValid(string $startDate, string $endDate): void
    {
        $command = new SaveRegulationGeneralInfoCommand($this->regulationOrderRecord);
        $command->startDate = new \DateTimeImmutable($startDate);
        $command->endDate = $endDate ? new \DateTimeImmutable($endDate) : null;

        $this->validator->validate($command, $this->constraintObj);
        $this->assertNoViolation();
    }

    public function testInvalidEndDateBeforeStartDate(): void
    {
        $command = new SaveRegulationGeneralInfoCommand($this->regulationOrderRecord);
        $command->startDate = new \DateTimeImmutable('2023-03-12');
        $command->endDate = new \DateTimeImmutable('2023-03-11');

        $this->validator->validate($command, $this->constraintObj);
        $this->buildViolation('regulation.error.end_date_before_start_date')
            ->setParameter('{{ compared_value }}', '12/03/2023')
            ->atPath('property.path.endDate')
            ->assertRaised();
    }
}
