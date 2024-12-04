<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\View;

use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
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
            organizationLogo: '/path/to/logo.jpg',
            organizationUuid: 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
            status: RegulationOrderRecordStatusEnum::DRAFT->value,
            regulationOrderUuid: 'ed97924b-bdc5-421a-b6e8-ac3ee6b16a7e',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            subject: RegulationSubjectEnum::OTHER->value,
            otherCategoryText: 'Other category 1',
            title: 'title 1',
            startDate: $startDate,
            endDate: $endDate,
        );

        $this->assertTrue($generalInfo->isDraft());

        $generalInformation2 = new GeneralInfoView(
            uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            identifier: 'FO1/2024',
            organizationName: 'DiaLog',
            organizationLogo: '/path/to/logo.jpg',
            organizationUuid: 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
            status: RegulationOrderRecordStatusEnum::PUBLISHED->value,
            regulationOrderUuid: '8a32e881-a683-4caa-976f-6882296bc29b',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            subject: RegulationSubjectEnum::OTHER->value,
            otherCategoryText: 'Other category 1',
            title: 'title 1',
            startDate: $startDate,
            endDate: $endDate,
        );

        $this->assertFalse($generalInformation2->isDraft());
    }
}
