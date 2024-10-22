<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map\Fragment;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapDataControllerTest extends AbstractWebTestCase
{
    public function testGetFilters(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson?map_filter_form%5Bcategory%5D=permanents_only&map_filter_form%5B_token%5D=4ef8515d4d5e05fb9f.8Zo-nuv_uVZHlzVSYd0sF6Lgr2Sv0ZOEh2efPahYS6g.vP9H-bO00Wcm51QbUuUdfe6SwwnmptT00QLoBJ87CuTF7kTqparQYCLQRg');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = $client->getResponse()->getContent();
        $this->assertSame('{"type":"FeatureCollection","features":[]}', $data);
    }

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

    public function testMeasureDatesFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson?map_filter_form[measureTypes][]=noEntry&map_filter_form[measureTypes][]=speedLimitation&map_filter_form[displayPermanentRegulations]=yes&map_filter_form[displayTemporaryRegulations]=yes&map_filter_form[startDate]=2023-05-11&map_filter_form[endDate]=2023-06-02');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $data = $client->getResponse()->getContent();
        $this->assertSame('{"type":"FeatureCollection","features":[]}', $data);
    }

    public function testMeasureDatesFilterWithoutEndDate(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson?map_filter_form[measureTypes][]=noEntry&map_filter_form[measureTypes][]=speedLimitation&map_filter_form[displayPermanentRegulations]=yes&map_filter_form[displayTemporaryRegulations]=yes&map_filter_form[startDate]=2023-09-06&map_filter_form[endDate]=');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $data = $client->getResponse()->getContent();
        $this->assertSame('{"type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"MultiLineString","coordinates":[[[3.023890325,50.570177599],[3.023850386,50.570151247]],[[3.023890325,50.570177599],[3.024458944,50.570503315]],[[3.024458944,50.570503315],[3.024500711,50.570527945]],[[3.024500711,50.570527945],[3.024501619,50.570528488]],[[3.024501619,50.570528488],[3.025116052,50.570901355]],[[3.025116052,50.570901355],[3.02515503,50.570929555]],[[3.023850386,50.570151247],[3.02384667,50.570148629]],[[3.023475742,50.569868822],[3.023440948,50.569835923]],[[3.023475742,50.569868822],[3.02384667,50.570148629]],[[3.02515503,50.570929555],[3.025159053,50.570932711]],[[3.025159053,50.570932711],[3.025653937,50.571355649]],[[3.025653937,50.571355649],[3.02569009,50.57138952]],[[3.02569009,50.57138952],[3.025691455,50.571390856]],[[3.025691455,50.571390856],[3.026131049,50.571842058]],[[3.026131049,50.571842058],[3.026159516,50.571877523]],[[3.023440948,50.569835923],[3.02343789,50.569832708]],[[3.023149663,50.569492048],[3.023119275,50.569455721]],[[3.023149663,50.569492048],[3.02343789,50.569832708]],[[3.022717354,50.568969715],[3.023119183,50.56945561]],[[3.023119275,50.569455721],[3.023119183,50.56945561]],[[3.026159516,50.571877523],[3.02616073,50.571879188]],[[3.02616073,50.571879188],[3.027150974,50.57338937]]]},"properties":{"location_uuid":"066e984f-4746-78f8-8000-dce555b28604","measure_type":"noEntry"}}]}', $data);
    }

    public function testMeasureDatesFilterWithoutStartDate(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson?map_filter_form[measureTypes][]=noEntry&map_filter_form[measureTypes][]=speedLimitation&map_filter_form[displayPermanentRegulations]=yes&map_filter_form[displayTemporaryRegulations]=yes&map_filter_form[startDate]=&map_filter_form[endDate]=2021-09-02');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $data = $client->getResponse()->getContent();
        $this->assertSame('{"type":"FeatureCollection","features":[]}', $data);
    }
}
