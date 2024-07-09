<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map\Fragment;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapDataControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/data.geojson');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = $client->getResponse()->getContent();

        $this->assertSame('{"type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"LineString","coordinates":[[1.35644,44.01574],[1.35426,44.01663]]},"properties":{"location_uuid":"06548f85-d545-7b45-8000-8a23c45850b3","measure_type":"noEntry"}},{"type":"Feature","geometry":{"type":"LineString","coordinates":[[4.81381,49.59185],[4.81474,49.59187],[4.81667,49.59211],[4.81819,49.59221],[4.81929,49.59248],[4.81989,49.59252],[4.82981,49.59109],[4.83017,49.59088],[4.83072,49.59008],[4.83104,49.58978],[4.8314,49.58957],[4.83355,49.58854],[4.83422,49.58823],[4.83697,49.58724],[4.83738,49.58714],[4.83847,49.58701],[4.83924,49.5868],[4.84004,49.5864],[4.84164,49.58543],[4.84291,49.5849],[4.84356,49.58469],[4.84966,49.58299],[4.8503,49.5829],[4.85131,49.58303],[4.85188,49.58292],[4.85279,49.58259],[4.85466,49.58175],[4.8554,49.5815],[4.85752,49.58092],[4.85814,49.58061],[4.85925,49.5797],[4.86115,49.57873],[4.86367,49.57725],[4.8654,49.57604],[4.86772,49.57489],[4.86986,49.57406],[4.87035,49.57373],[4.87068,49.57287],[4.87101,49.57249],[4.87274,49.5715],[4.87307,49.57112],[4.87335,49.57054],[4.87386,49.5702],[4.87474,49.56974],[4.87501,49.56954],[4.87526,49.56916],[4.87567,49.56776],[4.87581,49.56741],[4.87615,49.56683],[4.87636,49.56659],[4.87653,49.56651],[4.87675,49.56648],[4.87856,49.56689],[4.87904,49.56691],[4.87947,49.56684],[4.88058,49.56655],[4.88097,49.5664],[4.88124,49.56619],[4.88205,49.56528],[4.88227,49.56515],[4.88251,49.56508],[4.885,49.56496],[4.88602,49.56474],[4.88745,49.56433],[4.8878,49.56493],[4.88798,49.56511],[4.88821,49.56515],[4.88898,49.56505],[4.88941,49.56515],[4.88955,49.56528],[4.88972,49.56563],[4.89008,49.56608],[4.89041,49.56643],[4.89062,49.56654],[4.89523,49.56633],[4.8956,49.56634],[4.89575,49.56641],[4.8958,49.56653],[4.89572,49.56696],[4.89541,49.56808],[4.89454,49.56938],[4.89422,49.57023],[4.89398,49.57103],[4.89394,49.57135],[4.89403,49.57163],[4.89426,49.57187],[4.89537,49.57244],[4.8958,49.57275],[4.89649,49.57356],[4.89662,49.57377],[4.89663,49.57411],[4.89613,49.57593],[4.8961,49.57629],[4.89614,49.57695]]},"properties":{"location_uuid":"065f94ef-ea0a-7ab5-8000-bd5686102151","measure_type":"noEntry"}}]}', $data);
    }

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
}
