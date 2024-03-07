<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IgnWfsMockClient extends MockHttpClient
{
    public function __construct()
    {
        $callback = fn ($method, $url, $options) => $this->handleRequests($method, $url, $options);
        parent::__construct($callback);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        if ($method === 'GET' && str_starts_with($url, 'https://data.geopf.fr/wfs/ows')) {
            return $this->getWfsMock($options);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method $url");
    }

    private function getWfsMock($options): MockResponse
    {
        if ($options['query']['TYPENAME'] === 'BDTOPO_V3:voie_nommee' && str_contains($options['query']['cql_filter'], 'route du grand brossais')) {
            $body = [
                'features' => [
                    [
                        // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27route%20du%20grand%20brossais%27%20AND%20code_insee=44195&PropertyName=geometrie,id_pseudo_fpb
                        'geometry' => json_decode('{"type":"MultiLineString","coordinates":[[[-1.93900561,47.34611977],[-1.93881261,47.34614242],[-1.93862895,47.34618]],[[-1.94156143,47.34627613],[-1.94141949,47.34627676],[-1.94107,47.3462718],[-1.94073762,47.34626521],[-1.94044736,47.34624059],[-1.94002252,47.34619554],[-1.93967674,47.34615885],[-1.93940971,47.34612692],[-1.93920009,47.3461133],[-1.93900561,47.34611977]],[[-1.94782475,47.34625455],[-1.94772148,47.34625715],[-1.94736744,47.34624609],[-1.94687427,47.34622292],[-1.94662516,47.34621279],[-1.94604524,47.3461843],[-1.94554728,47.34616674],[-1.94501983,47.34616036]],[[-1.93862895,47.34618],[-1.93856253,47.34619365],[-1.93810681,47.34628881],[-1.93776551,47.34637279],[-1.93753691,47.34644115],[-1.93732707,47.34654028]],[[-1.94406201,47.34619306],[-1.94356307,47.346208],[-1.9430443,47.34622379],[-1.94264103,47.34624007],[-1.94217406,47.34625635],[-1.941718,47.34627578],[-1.94156143,47.34627613]],[[-1.94501983,47.34616036],[-1.94455905,47.34617188],[-1.94406201,47.34619306]],[[-1.93625155,47.34686565],[-1.93618471,47.34688924],[-1.9357484,47.34702236]],[[-1.92909849,47.34789133],[-1.92841676,47.34789593],[-1.92816427,47.34789223]],[[-1.9357484,47.34702236],[-1.9353862,47.34710992],[-1.93490428,47.34722332],[-1.93424563,47.34737761],[-1.93374519,47.34750623],[-1.93340389,47.34759019],[-1.93299607,47.34768691],[-1.93291188,47.34770943]],[[-1.9299491,47.34789037],[-1.92909849,47.34789133]],[[-1.93291188,47.34770943],[-1.93268153,47.34777334],[-1.93248952,47.34782119],[-1.93234592,47.34784714],[-1.93207756,47.34785854],[-1.93140947,47.3478671],[-1.93070816,47.34787525],[-1.93059414,47.34787649],[-1.9299491,47.34789037]],[[-1.93732707,47.34654028],[-1.93719248,47.34653428],[-1.9369894,47.3465339]],[[-1.9369894,47.3465339],[-1.93693099,47.34659141],[-1.93684045,47.34664577],[-1.93673693,47.34670339],[-1.93656674,47.34675754],[-1.93625155,47.34686565]]]}'),
                        'properties' => [
                            'id_pseudo_fpb' => '441950137',
                        ],
                    ],
                ],
            ];
        } elseif (str_contains($options['query']['cql_filter'], "strStripAccents(strReplace(nom_minuscule, '-', ' ', true))=strStripAccents(strReplace('rue saint victor', '-', ' ', true))")) {
            $body = [
                'features' => [
                    [
                        // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20saint-victor%27%20AND%20code_insee=59368&PropertyName=geometrie,id_pseudo_fpb
                        'geometry' => json_decode('{"type":"MultiLineString","coordinates":[[[3.06773711,50.65302845],[3.06770772,50.65320974],[3.0676489,50.65356155],[3.06761116,50.65383438],[3.06756215,50.65413324],[3.06756371,50.65424093]]]}'),
                        'properties' => [
                            'id_pseudo_fpb' => '593681470',
                        ],
                    ],
                ],
            ];
        } elseif ($options['query']['TYPENAME'] === 'BDTOPO_V3:voie_nommee' && str_contains($options['query']['cql_filter'], 'rue monge')) {
            // E2E tests
            $body = [
                'features' => [
                    [
                        // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20monge%27%20AND%20code_insee=21231&PropertyName=geometrie,id_pseudo_fpb
                        'geometry' => json_decode('{"type":"MultiLineString","coordinates":[[[5.03168932,47.31771662],[5.03177572,47.31772501],[5.03185628,47.31775422],[5.03193864,47.317796],[5.03200397,47.31784259]],[[5.03200397,47.31784259],[5.03190919,47.31782264],[5.03181621,47.31781526],[5.03173705,47.31782295],[5.03169758,47.31782994],[5.03165551,47.31783788]],[[5.03213156,47.31788989],[5.03200397,47.31784259]],[[5.03256244,47.31811288],[5.03232346,47.31799189],[5.03213156,47.31788989]],[[5.03256244,47.31811288],[5.03277057,47.3182236]],[[5.03413544,47.31891111],[5.03421187,47.31893589],[5.03454423,47.31903993],[5.0346421,47.31907154],[5.03473071,47.3191033],[5.03485994,47.31915867]],[[5.03485994,47.31915867],[5.03496222,47.31920191],[5.03510435,47.31928316]],[[5.03510435,47.31928316],[5.03521143,47.31934793],[5.03530781,47.31941017]],[[5.03530781,47.31941017],[5.03581835,47.31974252],[5.03588215,47.31978373],[5.03622198,47.31997949]],[[5.03277057,47.3182236],[5.03291609,47.31832461],[5.03328019,47.31853256],[5.03346836,47.31860581],[5.03364517,47.31869367]],[[5.03364517,47.31869367],[5.03376968,47.31876443],[5.03385727,47.31880432],[5.03393536,47.31883807],[5.03402271,47.31887167],[5.03413544,47.31891111]]]}'),
                        'properties' => [
                            'id_pseudo_fpb' => '212316030',
                        ],
                    ],
                ],
            ];
        } elseif ($options['query']['TYPENAME'] === 'BDTOPO_V3:voie_nommee' && str_contains($options['query']['cql_filter'], 'rue de la république')) {
            $body = [
                'features' => [
                    [
                        // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20de%20la%20r%C3%A9publique%27%20AND%20code_insee=82121&PropertyName=geometrie,id_pseudo_fpb
                        // Reworked to use a single part (road line returned by IGN appears fragmented in 2 parts)
                        'geometry' => json_decode('{"type":"MultiLineString","crs":{"type":"name","properties":{"name":"EPSG:2154"}},"coordinates":[[[1.35643852,44.01573612],[1.35634358,44.01578421],[1.35628051,44.01580846],[1.35620232,44.01583789],[1.35573093,44.01600635],[1.35515052,44.01623528],[1.3550483,44.01627605],[1.35476043,44.01639595],[1.35431163,44.01660254],[1.35418135,44.0166645],[1.35375252,44.01687049],[1.35354078,44.01698883],[1.35332393,44.01711159],[1.35325547,44.01714927],[1.35275247,44.01741806],[1.3527081,44.0174426],[1.35249763,44.01756005],[1.35244427,44.01759346],[1.35218587,44.01777231],[1.35197056,44.0179275],[1.35183958,44.01801374],[1.35155475,44.01820029]]]}'),
                        'properties' => [
                            'id_pseudo_fpb' => '821216800',
                        ],
                    ],
                ],
            ];
        } else {
            $body = [
                'features' => [],
            ];
        }

        return new MockResponse(
            json_encode($body),
            ['http_code' => 200],
        );
    }
}
