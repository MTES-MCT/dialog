<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map\Fragment;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapDataControllerTest extends AbstractWebTestCase
{
    public function testMeasureTypesFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson?map_filter_form[measureTypes][]=noEntry&map_filter_form[displayPermanentRegulations]=yes&map_filter_form[displayTemporaryRegulations]=yes');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $jsonData = $client->getResponse()->getContent();
        $dataArray = json_decode($jsonData, true);

        $this->assertCount(8, $dataArray['features']);

        foreach ($dataArray['features'] as $feature) {
            $this->assertSame(MeasureTypeEnum::NO_ENTRY->value, $feature['properties']['measure_type']);
        }
    }

    public function testRegulationTypeFilterNone(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson?map_filter_form[measureTypes][]=noEntry');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $jsonData = $client->getResponse()->getContent();
        $dataArray = json_decode($jsonData, true);
        $this->assertCount(0, $dataArray['features']);
    }

    public function testRegulationTypeFilterPermanentOnly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson?map_filter_form[measureTypes][]=noEntry&map_filter_form[displayPermanentRegulations]=yes');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $jsonData = $client->getResponse()->getContent();
        $dataArray = json_decode($jsonData, true);

        $this->assertCount(0, $dataArray['features']); // None published in test data
    }

    public function testRegulationTypeFilterTemporaryOnly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson?map_filter_form[measureTypes][]=noEntry&map_filter_form[displayTemporaryRegulations]=yes');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $jsonData = $client->getResponse()->getContent();
        $dataArray = json_decode($jsonData, true);

        $this->assertCount(8, $dataArray['features']);

        foreach ($dataArray['features'] as $feature) {
            $this->assertNotSame(RegulationOrderCategoryEnum::PERMANENT_REGULATION->value, $feature['properties']['regulation_category']);
        }
    }

    private function provideTestMeasureDatesFilter(): array
    {
        return [
            'interval-no-result' => [
                'queryString' => '&map_filter_form[startDate]=2018-12-10&map_filter_form[endDate]=2018-12-10',
                'locationUuids' => [],
            ],
            'interval-both-exact' => [
                'queryString' => '&map_filter_form[startDate]=2023-03-28&map_filter_form[endDate]=2023-03-28',
                'locationUuids' => [
                    LocationFixture::UUID_PUBLISHED,
                    LocationFixture::UUID_PUBLISHED2,
                    LocationFixture::UUID_PUBLISHED3,
                    LocationFixture::UUID_PUBLISHED4,
                ],
            ],
            // Test on cifsPeriod2 (2023-06-02 -> 2023-06-06)
            'interval-start-exact' => [
                'queryString' => '&map_filter_form[startDate]=2023-06-01&map_filter_form[endDate]=2023-06-02',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                ],
            ],
            'interval-start-cover' => [
                'queryString' => '&map_filter_form[startDate]=2023-06-01&map_filter_form[endDate]=2023-06-03',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                ],
            ],
            'interval-cover' => [
                'queryString' => '&map_filter_form[startDate]=2023-06-01&map_filter_form[endDate]=2023-06-08',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                ],
            ],
            'interval-exact' => [
                'queryString' => '&map_filter_form[startDate]=2023-06-02&map_filter_form[endDate]=2023-06-06',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                ],
            ],
            'interval-contained' => [
                'queryString' => '&map_filter_form[startDate]=2023-06-03&map_filter_form[endDate]=2023-06-05',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                ],
            ],
            'interval-end-cover' => [
                'queryString' => '&map_filter_form[startDate]=2023-06-04&map_filter_form[endDate]=2023-06-08',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                ],
            ],
            'interval-end-exact' => [
                'queryString' => '&map_filter_form[startDate]=2023-06-06&map_filter_form[endDate]=2023-06-08',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                ],
            ],
            'start-only' => [
                'queryString' => '&map_filter_form[startDate]=2023-11-02',
                'locationUuids' => [
                    LocationFixture::UUID_LITTERALIS,
                ],
            ],
            'end-only' => [
                'queryString' => '&map_filter_form[endDate]=2021-12-01',
                'locationUuids' => [
                    LocationFixture::UUID_OUTDATED_CIFS,
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideTestMeasureDatesFilter
     */
    public function testMeasureDatesFilter(string $queryString, array $locationUuids): void
    {
        $client = static::createClient();

        $url = \sprintf('/carte/data.geojson?map_filter_form[measureTypes][]=noEntry&map_filter_form[measureTypes][]=speedLimitation&map_filter_form[displayTemporaryRegulations]=yes%s', $queryString);
        $client->request('GET', $url);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $data = json_decode($client->getResponse()->getContent(), true);

        $actualLocationUuids = [];

        foreach ($data['features'] as $feature) {
            $actualLocationUuids[] = $feature['properties']['location_uuid'];
        }

        $this->assertEquals($locationUuids, $actualLocationUuids);
    }
}
