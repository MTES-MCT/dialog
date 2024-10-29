<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map\Fragment;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapDataControllerTest extends AbstractWebTestCase
{
    public function testMeasureTypesFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson?map_filter_form[measureTypes][]=noEntry');

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

    private function provideTestMeasureDatesFilter(): array
    {
        return [
            'interval-no-result' => [
                'queryString' => '&map_filter_form[startDate]=2018-12-10&map_filter_form[endDate]=2018-12-10',
                'locationUuids' => [],
            ],
            // Test on cifsPeriod2 (2023-09-03 -> 2023-09-06)
            // and litteralisRegulationOrder (2023-06-03 -> 2023-11-10, no periods)
            'interval-start-exact' => [
                'queryString' => '&map_filter_form[startDate]=2023-09-01&map_filter_form[endDate]=2023-09-03',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                    LocationFixture::UUID_LITTERALIS,
                ],
            ],
            'interval-start-cover' => [
                'queryString' => '&map_filter_form[startDate]=2023-09-01&map_filter_form[endDate]=2023-09-04',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                    LocationFixture::UUID_LITTERALIS,
                ],
            ],
            'interval-cover' => [
                'queryString' => '&map_filter_form[startDate]=2023-09-01&map_filter_form[endDate]=2023-09-08',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                    LocationFixture::UUID_LITTERALIS,
                ],
            ],
            'interval-exact' => [
                'queryString' => '&map_filter_form[startDate]=2023-09-03&map_filter_form[endDate]=2023-09-06',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                    LocationFixture::UUID_LITTERALIS,
                ],
            ],
            'interval-contained' => [
                'queryString' => '&map_filter_form[startDate]=2023-09-04&map_filter_form[endDate]=2023-09-05',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                    LocationFixture::UUID_LITTERALIS,
                ],
            ],
            'interval-end-cover' => [
                'queryString' => '&map_filter_form[startDate]=2023-09-04&map_filter_form[endDate]=2023-09-08',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                    LocationFixture::UUID_LITTERALIS,
                ],
            ],
            'interval-end-exact' => [
                'queryString' => '&map_filter_form[startDate]=2023-09-06&map_filter_form[endDate]=2023-09-08',
                'locationUuids' => [
                    LocationFixture::UUID_CIFS_NAMED_STREET,
                    LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
                    LocationFixture::UUID_LITTERALIS,
                ],
            ],
            // 'no-startDate' => [
            //     'queryString' => '&endDate=2023-06-02',
            //     'locationUuids' => [],
            // ],
            // 'no-endDate' => [
            //     'queryString' => '&startDate=2023-06-02',
            //     'locationUuids' => [],
            // ],
        ];
    }

    /**
     * @group only
     *
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
