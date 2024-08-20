<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Infrastructure\Litteralis\LitteralisPeriodParser;
use PHPUnit\Framework\TestCase;

final class LitteralisPeriodParserTest extends TestCase
{
    private $parser;
    private $tz;

    protected function setUp(): void
    {
        $this->parser = new LitteralisPeriodParser();
        $this->tz = new \DateTimeZone('Etc/GMT-1'); // Independant of Daylight Saving Time (DST)
    }

    private function provideParse(): array
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
                'expectedResult' => ['timeSlots' => [$timeSlot1]],
            ],
            'hours2' => [
                'value' => '08 h 00 à 18 h 00',
                'expectedResult' => ['timeSlots' => [$timeSlot1]],
            ],
            'hours3' => [
                'value' => 'DE 7 H 30 À 17 H 30',
                'expectedResult' => ['timeSlots' => [$timeSlot2]],
            ],
            'hours3-typo' => [
                'value' => 'DE 7 H 30 0 17 H 30',
                'expectedResult' => ['timeSlots' => [$timeSlot2]],
            ],
            'hours4' => [
                'value' => '8h à 18h',
                'expectedResult' => ['timeSlots' => [$timeSlot1]],
            ],
            'hours5' => [
                'value' => 'de 8h à 18h',
                'expectedResult' => ['timeSlots' => [$timeSlot1]],
            ],
            'hours6' => [
                'value' => 'de 7 heures 30 à 17 heures 30',
                'expectedResult' => ['timeSlots' => [$timeSlot2]],
            ],
        ];
    }

    /**
     * @dataProvider provideParse
     */
    public function testParse(string $value, array $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->parser->parse($value, $this->tz));
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
        $this->assertNull($this->parser->parse($value, $this->tz));
    }
}
