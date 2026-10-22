<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation;

use App\Application\Regulation\NumberedRoadLabelMaker;
use App\Domain\Regulation\Location\NumberedRoad;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NumberedRoadLabelMakerTest extends TestCase
{
    private NumberedRoadLabelMaker $labelMaker;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            function (string $id, array $parameters = []): string {
                $catalogue = [
                    'regulation.location.reference_point' => 'du PR %fromPointNumber%+%fromAbscissa% (côté %fromSide%) au PR %toPointNumber%+%toAbscissa% (côté %toSide%)',
                ];

                return strtr($catalogue[$id] ?? $id, $parameters);
            },
        );
        $this->labelMaker = new NumberedRoadLabelMaker($translator);
    }

    public function testMakeWithAdministratorAndReferencePoints(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad->method('getRoadNumber')->willReturn('D66');
        $numberedRoad->method('getAdministrator')->willReturn('Lozère');
        $numberedRoad->method('getFromPointNumber')->willReturn('10');
        $numberedRoad->method('getFromAbscissa')->willReturn(50);
        $numberedRoad->method('getFromSide')->willReturn('U');
        $numberedRoad->method('getToPointNumber')->willReturn('13');
        $numberedRoad->method('getToAbscissa')->willReturn(0);
        $numberedRoad->method('getToSide')->willReturn('U');

        $this->assertSame(
            'D66 (Lozère) du PR 10+50 (côté U) au PR 13+0 (côté U)',
            $this->labelMaker->make($numberedRoad),
        );
    }

    public function testMakeCoalescesNullAbscissaToZero(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad->method('getRoadNumber')->willReturn('D14');
        $numberedRoad->method('getAdministrator')->willReturn('Seine-Saint-Denis');
        $numberedRoad->method('getFromPointNumber')->willReturn('1');
        $numberedRoad->method('getFromAbscissa')->willReturn(null);
        $numberedRoad->method('getFromSide')->willReturn('U');
        $numberedRoad->method('getToPointNumber')->willReturn('4');
        $numberedRoad->method('getToAbscissa')->willReturn(null);
        $numberedRoad->method('getToSide')->willReturn('U');

        $this->assertSame(
            'D14 (Seine-Saint-Denis) du PR 1+0 (côté U) au PR 4+0 (côté U)',
            $this->labelMaker->make($numberedRoad),
        );
    }

    public function testMakeWithoutAdministrator(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad->method('getRoadNumber')->willReturn('D920');
        $numberedRoad->method('getAdministrator')->willReturn(null);
        $numberedRoad->method('getFromPointNumber')->willReturn(null);
        $numberedRoad->method('getToPointNumber')->willReturn(null);

        $this->assertSame('D920', $this->labelMaker->make($numberedRoad));
    }

    public function testMakeWithoutReferencePointsWhenOnePointIsMissing(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad->method('getRoadNumber')->willReturn('D920');
        $numberedRoad->method('getAdministrator')->willReturn('Département');
        $numberedRoad->method('getFromPointNumber')->willReturn('10');
        $numberedRoad->method('getToPointNumber')->willReturn(null);

        $this->assertSame('D920 (Département)', $this->labelMaker->make($numberedRoad));
        $this->assertSame('', $this->labelMaker->makeReferencePoints($numberedRoad));
    }

    public function testMakeReferencePointsSupportsPointNumberZero(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad->method('getFromPointNumber')->willReturn('0');
        $numberedRoad->method('getFromAbscissa')->willReturn(0);
        $numberedRoad->method('getFromSide')->willReturn('U');
        $numberedRoad->method('getToPointNumber')->willReturn('1');
        $numberedRoad->method('getToAbscissa')->willReturn(0);
        $numberedRoad->method('getToSide')->willReturn('U');

        $this->assertSame(
            'du PR 0+0 (côté U) au PR 1+0 (côté U)',
            $this->labelMaker->makeReferencePoints($numberedRoad),
        );
    }
}
