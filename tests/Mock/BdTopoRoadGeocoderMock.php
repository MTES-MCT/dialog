<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;

final class BdTopoRoadGeocoderMock implements RoadGeocoderInterface
{
    public function computeRoadLine(string $roadName, string $inseeCode): string
    {
        return match ([$roadName, $inseeCode]) {
            ['Rue Saint-Victor', '59368'] => json_encode([
                'type' => 'MultiLineString',
                'coordinates' => [[[3.06773711, 50.65302845], [3.06770772, 50.65320974], [3.0676489, 50.65356155], [3.06761116, 50.65383438], [3.06756215, 50.65413324], [3.06756371, 50.65424093]]],
                'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
            ]),
            ['Rue Monge', '21231'] => json_encode([
                'type' => 'MultiLineString',
                'coordinates' => [[[5.03168932, 47.31771662], [5.03177572, 47.31772501], [5.03185628, 47.31775422], [5.03193864, 47.317796], [5.03200397, 47.31784259]], [[5.03200397, 47.31784259], [5.03190919, 47.31782264], [5.03181621, 47.31781526], [5.03173705, 47.31782295], [5.03169758, 47.31782994], [5.03165551, 47.31783788]], [[5.03213156, 47.31788989], [5.03200397, 47.31784259]], [[5.03256244, 47.31811288], [5.03232346, 47.31799189], [5.03213156, 47.31788989]], [[5.03256244, 47.31811288], [5.03277057, 47.3182236]], [[5.03413544, 47.31891111], [5.03421187, 47.31893589], [5.03454423, 47.31903993], [5.0346421, 47.31907154], [5.03473071, 47.3191033], [5.03485994, 47.31915867]], [[5.03485994, 47.31915867], [5.03496222, 47.31920191], [5.03510435, 47.31928316]], [[5.03510435, 47.31928316], [5.03521143, 47.31934793], [5.03530781, 47.31941017]], [[5.03530781, 47.31941017], [5.03581835, 47.31974252], [5.03588215, 47.31978373], [5.03622198, 47.31997949]], [[5.03277057, 47.3182236], [5.03291609, 47.31832461], [5.03328019, 47.31853256], [5.03346836, 47.31860581], [5.03364517, 47.31869367]], [[5.03364517, 47.31869367], [5.03376968, 47.31876443], [5.03385727, 47.31880432], [5.03393536, 47.31883807], [5.03402271, 47.31887167], [5.03413544, 47.31891111]]],
                'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
            ]),
            ['Route du Grand Brossais', '44195'] => json_encode([
                'type' => 'MultiLineString',
                'coordinates' => [[[-1.939005606, 47.346119771], [-1.938812609, 47.346142418], [-1.93862895, 47.346180001]], [[-1.941561432, 47.346276131], [-1.941419489, 47.346276764], [-1.941070001, 47.346271804], [-1.940737616, 47.346265213], [-1.940447363, 47.346240591], [-1.940022518, 47.346195541], [-1.939676742, 47.346158849], [-1.939409705, 47.346126922], [-1.939200094, 47.346113295], [-1.939005606, 47.346119771]], [[-1.94782475, 47.34625455], [-1.947721475, 47.34625715], [-1.947367441, 47.346246089], [-1.946874269, 47.346222919], [-1.946625164, 47.346212794], [-1.946045239, 47.346184299], [-1.945547278, 47.34616674], [-1.945019825, 47.346160357]], [[-1.93862895, 47.346180001], [-1.938562525, 47.346193652], [-1.938106806, 47.346288815], [-1.937765513, 47.346372792], [-1.937536911, 47.34644115], [-1.937327069, 47.346540278]], [[-1.944062012, 47.346193057], [-1.94356307, 47.346208002], [-1.943044298, 47.346223791], [-1.94264103, 47.346240068], [-1.942174061, 47.346256352], [-1.941717998, 47.346275777], [-1.941561432, 47.346276131]], [[-1.945019825, 47.346160357], [-1.944559053, 47.346171876], [-1.944062012, 47.346193057]], [[-1.936251551, 47.346865654], [-1.936184713, 47.346889243], [-1.935748404, 47.347022356]], [[-1.929098491, 47.34789133], [-1.928416765, 47.347895935], [-1.928164268, 47.347892228]], [[-1.935748404, 47.347022356], [-1.9353862, 47.347109922], [-1.934904278, 47.347223324], [-1.934245628, 47.347377613], [-1.933745195, 47.347506229], [-1.933403887, 47.347590194], [-1.932996068, 47.347686907], [-1.932911877, 47.347709428]], [[-1.929949098, 47.34789037], [-1.929098491, 47.34789133]], [[-1.932911877, 47.347709428], [-1.932681528, 47.34777334], [-1.932489515, 47.34782119], [-1.932345918, 47.347847137], [-1.932077558, 47.347858545], [-1.931409466, 47.347867097], [-1.930708159, 47.347875254], [-1.930594139, 47.347876491], [-1.929949098, 47.34789037]], [[-1.937327069, 47.346540278], [-1.937192478, 47.346534279], [-1.936989395, 47.346533901]], [[-1.936989395, 47.346533901], [-1.936930991, 47.346591407], [-1.936840446, 47.346645771], [-1.93673693, 47.346703392], [-1.936566737, 47.346757536], [-1.936251551, 47.346865654]]],
                'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
            ]),
            ['Rue de la République', '82121'] => json_encode([
                'type' => 'MultiLineString',
                'coordinates' => [[[1.35643852, 44.01573612], [1.35634358, 44.01578421], [1.35628051, 44.01580846], [1.35620232, 44.01583789], [1.35573093, 44.01600635], [1.35515052, 44.01623528], [1.3550483, 44.01627605], [1.35476043, 44.01639595], [1.35431163, 44.01660254], [1.35418135, 44.0166645], [1.35375252, 44.01687049], [1.35354078, 44.01698883], [1.35332393, 44.01711159], [1.35325547, 44.01714927], [1.35275247, 44.01741806], [1.3527081, 44.0174426], [1.35249763, 44.01756005], [1.35244427, 44.01759346], [1.35218587, 44.01777231], [1.35197056, 44.0179275], [1.35183958, 44.01801374], [1.35155475, 44.01820029]]],
                'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
            ]),
            ['Rue de NOT_HANDLED_BY_MOCK', '59368'] => throw new GeocodingFailureException(),
            ['Route du HOUSENUMBER_GEOCODING_FAILURE', '44195'] => json_encode([
                'type' => 'MultiLineString',
                'coordinates' => [[[-1.939005606, 47.346119771], [-1.938812609, 47.346142418]]],
                'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
            ]),
            // Eudonet Paris integration test
            ['Boulevard de l\'Hôpital', '75113'] => json_encode([
                'type' => 'MultiLineString',
                'coordinates' => [[[2.356484293, 48.832800325], [2.356109059, 48.832297356], [2.356056855, 48.832222427]], [[2.356484293, 48.832800325], [2.356650466, 48.833023363], [2.35676572, 48.833177775]], [[2.356824681, 48.833257237], [2.35676572, 48.833177775]], [[2.358272032, 48.835203151], [2.35781505, 48.834587329]], [[2.357786886, 48.834551203], [2.356824681, 48.833257237]], [[2.35781505, 48.834587329], [2.357786886, 48.834551203]], [[2.35877202, 48.835867767], [2.358651339, 48.83571063]], [[2.359290608, 48.836572052], [2.35877202, 48.835867767]], [[2.360996746, 48.838872762], [2.360846652, 48.838668704]], [[2.360006248, 48.837540004], [2.359515764, 48.836875445], [2.359290608, 48.836572052]], [[2.360846652, 48.838668704], [2.360006248, 48.837540004]], [[2.358541412, 48.835565242], [2.358272032, 48.835203151]], [[2.358651339, 48.83571063], [2.358541412, 48.835565242]], [[2.360996746, 48.838872762], [2.36134685, 48.839321616], [2.361589334, 48.839658373], [2.361696024, 48.839846909]]],
                'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
            ]),
            default => throw new \UnexpectedValueException(sprintf('Mock not implemented: roadName=%s, inseeCode=%s', $roadName, $inseeCode)),
        };
    }

    public function findRoads(string $search, string $administrator): array
    {
        return match ($search) {
            'd32' => [
                [
                    'roadNumber' => 'D322',
                ],
            ],
            default => [],
        };
    }

    public function computeRoad(string $roadNumber, string $administrator): string
    {
        return match ([$administrator, $roadNumber]) {
            ['Ardèche', 'D110'] => '{"type":"MultiLineString","coordinates":[[[4.66349228,49.8207711],[4.66356107,49.82070816],[4.6636232,49.8206543],[4.66372513,49.82058551],[4.66385317,49.82050828],[4.66399657,49.82043354],[4.66415639,49.82035139],[4.6643028,49.82028379],[4.66443686,49.82022086],[4.66459579,49.82015399],[4.6647601,49.82008166]]]}',
            default => '',
        };
    }

    public function computeReferencePoint(string $lineGeometry, string $administrator, string $roadNumber, string $pointNumber, string $side, int $abscissa = 0): Coordinates
    {
        return match ([$administrator, $roadNumber, $pointNumber, $side]) {
            ['Ardèche', 'D110', '1', 'U'] => Coordinates::fromLonLat(3.162075419, 48.510493704),
            ['Ardèche', 'D110', '5', 'U'] => Coordinates::fromLonLat(3.201738314, 48.530088505),
            default => throw new RoadGeocodingFailureException(),
        };
    }
}
