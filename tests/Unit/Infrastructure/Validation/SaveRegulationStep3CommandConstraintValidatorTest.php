<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Validation;

use App\Application\Regulation\Command\Steps\SaveRegulationStep3Command;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Infrastructure\Validator\SaveRegulationStep3CommandConstraint;
use App\Infrastructure\Validator\SaveRegulationStep3CommandConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SaveRegulationStep3CommandConstraintValidatorTest extends ConstraintValidatorTestCase
{
    private $constraintObj;
    private $regulationOrderRecord;

    protected function setUp(): void
    {
        $this->defaultTimezone = 'UTC';
        parent::setUp();
        $this->constraintObj = new SaveRegulationStep3CommandConstraint();
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new SaveRegulationStep3CommandConstraintValidator(clientTimezone: 'Europe/Paris');
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
                'startDate' => '2022-12-07',
                'startTime' => '',
                'endDate' => '',
                'endTime' => '',
            ],
            [
                // Start date, and end date in the future
                'startDate' => '2022-12-07',
                'startTime' => '',
                'endDate' => '2022-12-17',
                'endTime' => '',
            ],
            [
                // Same date, with start time only
                'startDate' => '2022-12-07',
                'startTime' => '09:00',
                'endDate' => '2022-12-07',
                'endTime' => '',
            ],
            [
                // Same date, with end time only
                'startDate' => '2022-12-07',
                'startTime' => '',
                'endDate' => '2022-12-07',
                'endTime' => '06:00',
            ],
            [
                // Same date, with end time in the future
                'startDate' => '2022-12-07',
                'startTime' => '09:00',
                'endDate' => '2022-12-07',
                'endTime' => '10:00',
            ],
        ];
    }

    /**
     * @dataProvider provideValidCases
     */
    public function testValid(string $startDate, string $startTime, string $endDate, string $endTime): void
    {
        $command = new SaveRegulationStep3Command($this->regulationOrderRecord);
        $command->startDate = new \DateTimeImmutable($startDate);
        $command->startTime = $startTime ? new \DateTimeImmutable($startTime) : null;
        $command->endDate = $endDate ? new \DateTimeImmutable($endDate) : null;
        $command->endTime = $endTime ? new \DateTimeImmutable($endTime) : null;

        $this->validator->validate($command, $this->constraintObj);
        $this->assertNoViolation();
    }

    public function testInvalidEndTimeWithoutEndDate(): void
    {
        $command = new SaveRegulationStep3Command($this->regulationOrderRecord);
        $command->startDate = new \DateTimeImmutable('2022-12-17');
        $command->endTime = new \DateTimeImmutable('10:30:00');

        $this->validator->validate($command, $this->constraintObj);
        $this->buildViolation('regulation.step3.error.end_time_without_end_date')
            ->atPath('property.path.endDate')
            ->assertRaised();
    }

    public function testInvalidEndDateBeforeStartDate(): void
    {
        $command = new SaveRegulationStep3Command($this->regulationOrderRecord);
        $command->startDate = new \DateTimeImmutable('2022-12-17');
        $command->endDate = new \DateTimeImmutable('2022-12-16');

        $this->validator->validate($command, $this->constraintObj);
        $this->buildViolation('regulation.step3.error.end_date_before_start_date')
            ->setParameter('{{ compared_value }}', '17/12/2022')
            ->atPath('property.path.endDate')
            ->assertRaised();
    }

    public function testInvalidEndTimeBeforeStartTime(): void
    {
        $command = new SaveRegulationStep3Command($this->regulationOrderRecord);
        $command->startDate = new \DateTimeImmutable('2022-12-17');
        $command->startTime = new \DateTimeImmutable('10:30:00');
        $command->endDate = new \DateTimeImmutable('2022-12-17');
        $command->endTime = new \DateTimeImmutable('09:30:00');

        $this->validator->validate($command, $this->constraintObj);
        $this->buildViolation('regulation.step3.error.end_time_before_start_time')
            ->setParameter('{{ compared_value }}', '11h30')
            ->atPath('property.path.endTime')
            ->assertRaised();
    }
}
