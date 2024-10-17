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
}
