<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\View;

use App\Application\Regulation\View\GeneralInfoView;
use PHPUnit\Framework\TestCase;

final class GeneralInfoViewTest extends TestCase
{
    public function testView(): void
    {
        $startDate = new \DateTime('2022-12-07');
        $endDate = new \DateTime('2022-12-17');
        $generalInfo = new GeneralInfoView(
            uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            identifier: 'FO1/2024',
            organizationName: 'DiaLog',
            organizationUuid: 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
            status: 'draft',
            category: 'other',
            otherCategoryText: 'Other category 1',
            description: 'Description 1',
            startDate: $startDate,
            endDate: $endDate,
        );

        $this->assertTrue($generalInfo->isDraft());

        $generalInformation2 = new GeneralInfoView(
            uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            identifier: 'FO1/2024',
            organizationName: 'DiaLog',
            organizationUuid: 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
            status: 'published',
            category: 'other',
            otherCategoryText: 'Other category 1',
            description: 'Description 1',
            startDate: $startDate,
            endDate: $endDate,
        );

        $this->assertFalse($generalInformation2->isDraft());
    }
}
