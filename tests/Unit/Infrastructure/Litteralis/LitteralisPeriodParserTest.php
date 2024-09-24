<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\Litteralis\LitteralisPeriodParser;
use App\Infrastructure\Litteralis\LitteralisRecordEnum;
use PHPUnit\Framework\TestCase;

final class LitteralisPeriodParserTest extends TestCase
{
    private $parser;

    protected function setUp(): void
    {
        $tz = new \DateTimeZone('Etc/GMT-1'); // Independant of Daylight Saving Time (DST)
        $this->parser = new LitteralisPeriodParser($tz);
    }

    private function provideParseTimeSlots(): array
    {
        $timeSlot1 = new SaveTimeSlotCommand();
        $timeSlot1->startTime = new \DateTimeImmutable('07:00');
        $timeSlot1->endTime = new \DateTimeImmutable('17:00');

        $timeSlot2 = new SaveTimeSlotCommand();
        $timeSlot2->startTime = new \DateTimeImmutable('06:30');
        $timeSlot2->endTime = new \DateTimeImmutable('16:30');

        return [
            'hours1' => [
                'value' => 'de 08 h 00 à 18 h 00',
                'timeSlots' => [$timeSlot1],
            ],
            'hours2' => [
                'value' => '08 h 00 à 18 h 00',
                'timeSlots' => [$timeSlot1],
            ],
            'hours3' => [
                'value' => 'DE 7 H 30 À 17 H 30',
                'timeSlots' => [$timeSlot2],
            ],
            'hours3-typo' => [
                'value' => 'DE 7 H 30 0 17 H 30',
                'timeSlots' => [$timeSlot2],
            ],
            'hours4' => [
                'value' => '8h à 18h',
                'timeSlots' => [$timeSlot1],
            ],
            'hours5' => [
                'value' => 'de 8h à 18h',
                'timeSlots' => [$timeSlot1],
            ],
            'hours6' => [
                'value' => 'de 7 heures 30 à 17 heures 30',
                'timeSlots' => [$timeSlot2],
            ],
        ];
    }

    /**
     * @dataProvider provideParseTimeSlots
     */
    public function testParseTimeSlots(string $value, array $expectedResult): void
    {
        $properties = [];
        $reporter = $this->createMock(Reporter::class);

        $this->assertEquals($expectedResult, $this->parser->parseTimeSlots($value, $properties, $reporter));
    }

    private function provideUnparsable(): array
    {
        return [
            ['de 8h à 9h et de 16h à 17h'],
            ['Lundi, mardi, jeudi, vendredi de 8h à 9h et de 16h à 17h'],
            ['la journée'],
            ['le matin'],
            ['de 21h00 à 05h00 du 12/02/2024 au 12/04/2024 et du 19/08/2024 au 18/10/2024'],
            ['les nuits du 05/10/2023, 06/10/2023 et 07/10/2023 de 21h00 à 06h00'],
            ['de 21h00 à 06h00 du lundi soir au samedi matin'],
        ];
    }

    /**
     * @dataProvider provideUnparsable
     */
    public function testUnparsable(string $value): void
    {
        $properties = [
            'idemprise' => 'id1',
            'arretesrcid' => 'id2',
            'shorturl' => 'url',
        ];

        $reporter = $this->createMock(Reporter::class);

        $reporter
            ->expects(self::once())
            ->method('addError')
            ->with(LitteralisRecordEnum::ERROR_PERIOD_UNPARSABLE->value);

        $this->assertEquals([], $this->parser->parseTimeSlots($value, $properties, $reporter));
    }

    public function testNormalizeDates(): void
    {
        $parameters = [];

        $properties = [
            'arretedebut' => '2024-09-22T02:00:00Z',
            'arretefin' => '2024-09-22T02:00:00Z',
            'emprisedebut' => '',
            'emprisefin' => '',
        ];
        $reporter = $this->createMock(Reporter::class);

        $periodCommand = new SavePeriodCommand();
        $periodCommand->startDate = new \DateTimeImmutable('2024-09-21 23:00');
        $periodCommand->startTime = new \DateTimeImmutable('2024-09-21 23:00');
        $periodCommand->endDate = new \DateTimeImmutable('2024-09-22 22:59');
        $periodCommand->endTime = new \DateTimeImmutable('2024-09-22 22:59');
        $periodCommand->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $periodCommand->timeSlots = [];

        $this->assertEquals([$periodCommand], $this->parser->parsePeriods($parameters, $properties, $reporter));
    }
}
