<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Specification;

use App\Application\Regulation\View\ListItemLocationView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderRecordSummaryView;
use App\Domain\Regulation\Specification\CanAccessToRegulationDetail;
use PHPUnit\Framework\TestCase;

final class CanAccessToRegulationDetailTest extends TestCase
{
    public function testIsSatisfiedBy(): void
    {
        $period = $this->createMock(PeriodView::class);

        $view1 = new RegulationOrderRecordSummaryView(
            'd8376abe-1039-43a1-b333-680a1a6f5a22',
            'published',
            'Description',
            $period,
            new ListItemLocationView('Boulevard Ney', 'Paris'),
            null,
            null,
        );

        $view2 = new RegulationOrderRecordSummaryView(
            'd8376abe-1039-43a1-b333-680a1a6f5a22',
            'draft',
            'Description',
            null,
            null,
            null,
        );

        $view3 = new RegulationOrderRecordSummaryView(
            'd8376abe-1039-43a1-b333-680a1a6f5a22',
            'published',
            'Description',
            null,
            null,
            null,
        );

        $specification = new CanAccessToRegulationDetail();
        $this->assertTrue($specification->isSatisfiedBy($view1));
        $this->assertFalse($specification->isSatisfiedBy($view2));
        $this->assertFalse($specification->isSatisfiedBy($view3));
    }
}
