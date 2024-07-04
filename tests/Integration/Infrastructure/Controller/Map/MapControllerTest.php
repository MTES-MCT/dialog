<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/carte');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertMetaTitle('Carte - DiaLog', $crawler);
        $this->assertSame('{"type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"LineString","coordinates":[[1.35644,44.01574],[1.35426,44.01663]]},"properties":{"location_uuid":"06548f85-d545-7b45-8000-8a23c45850b3","measure_type":"noEntry"}},{"type":"Feature","geometry":{"type":"LineString","coordinates":[[4.81381,49.59185],[4.81474,49.59187],[4.8165,49.5921],[4.81802,49.59219],[4.81834,49.59223],[4.81916,49.59246],[4.81974,49.59253],[4.82054,49.59246],[4.82981,49.59109],[4.83017,49.59088],[4.83072,49.59008],[4.83104,49.58978],[4.83166,49.58944],[4.83355,49.58854],[4.83422,49.58823],[4.83719,49.58717],[4.83847,49.58701],[4.83924,49.5868],[4.84004,49.5864],[4.84152,49.58549],[4.84199,49.58525],[4.84291,49.5849],[4.84918,49.58311],[4.85005,49.5829],[4.85044,49.5829],[4.85112,49.58303],[4.85167,49.58298],[4.85253,49.5827],[4.85466,49.58175],[4.8554,49.5815],[4.85752,49.58092],[4.85814,49.58061],[4.85925,49.5797],[4.86115,49.57873],[4.86269,49.57784],[4.86405,49.57701],[4.8654,49.57604],[4.86798,49.57477],[4.86966,49.57415],[4.87005,49.57396],[4.87035,49.57373],[4.87068,49.57287],[4.87101,49.57249],[4.87274,49.5715],[4.87307,49.57112],[4.87335,49.57054],[4.87386,49.5702],[4.87489,49.56965],[4.87501,49.56954],[4.87526,49.56916],[4.87581,49.56741],[4.87615,49.56683],[4.87636,49.56659],[4.87653,49.56651],[4.87687,49.56649],[4.87841,49.56686],[4.8789,49.56692],[4.87997,49.56672],[4.88072,49.56651],[4.88111,49.56631],[4.88205,49.56528],[4.88227,49.56515],[4.88251,49.56508],[4.885,49.56496],[4.88602,49.56474],[4.88745,49.56433],[4.8878,49.56493],[4.88798,49.56511],[4.88821,49.56515],[4.88898,49.56505],[4.88941,49.56515],[4.88955,49.56528],[4.88972,49.56563],[4.88994,49.56592],[4.89041,49.56643],[4.89062,49.56654],[4.89087,49.56656],[4.89219,49.56645],[4.89523,49.56633],[4.8956,49.56634],[4.89575,49.56641],[4.89579,49.56662],[4.89541,49.56808],[4.89454,49.56938],[4.89422,49.57023],[4.89398,49.57103],[4.89394,49.57135],[4.89403,49.57163],[4.89426,49.57187],[4.89554,49.57254],[4.8959,49.57284],[4.89662,49.57377],[4.89663,49.57411],[4.8961,49.57612],[4.89614,49.57695]]},"properties":{"location_uuid":"065f94ef-ea0a-7ab5-8000-bd5686102151","measure_type":"noEntry"}}]}', $crawler->filter('#locations_as_geojson')->text());
    }

    public function testGetFilters(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/carte?map_filter_form%5Bcategory%5D=permanents_only&map_filter_form%5B_token%5D=4ef8515d4d5e05fb9f.8Zo-nuv_uVZHlzVSYd0sF6Lgr2Sv0ZOEh2efPahYS6g.vP9H-bO00Wcm51QbUuUdfe6SwwnmptT00QLoBJ87CuTF7kTqparQYCLQRg');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertMetaTitle('Carte - DiaLog', $crawler);
        $this->assertSame('{"type":"FeatureCollection","features":[]}', $crawler->filter('#locations_as_geojson')->text());
    }
}
