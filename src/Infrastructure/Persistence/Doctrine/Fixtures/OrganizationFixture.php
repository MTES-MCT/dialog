<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class OrganizationFixture extends Fixture
{
    public const SEINE_SAINT_DENIS_ID = 'e0d93630-acf7-4722-81e8-ff7d5fa64b66';
    public const SEINE_SAINT_DENIS_NAME = 'Département de Seine-Saint-Denis';
    public const REGION_IDF_ID = '3c46e94d-7ca2-4253-a9ea-0ce5fdb966a4';
    public const SAINT_OUEN_ID = 'ea9c0cfe-165f-49cf-934b-3a11c6e96b79';

    public function load(ObjectManager $manager): void
    {
        $seineSaintDenisOrg = (new Organization(self::SEINE_SAINT_DENIS_ID))
            ->setName(self::SEINE_SAINT_DENIS_NAME)
            ->setCreatedAt(new \DateTimeImmutable('2022-11-01'))
            ->setSiret('22930008201453')
            ->setLogo('/path/to/logo.jpeg')
            ->setCode('93')
            ->setCodeType(OrganizationCodeTypeEnum::DEPARTMENT->value)
            ->setGeometry('{"type":"Polygon","coordinates":[[[2.415986,48.85136],[2.412453,48.876443],[2.40936,48.880256],[2.400107,48.883821],[2.397609,48.894722],[2.395518,48.898265],[2.390837,48.900907],[2.38453,48.902148],[2.319887,48.90046],[2.320851,48.904666],[2.313768,48.914045],[2.319819,48.915937],[2.333627,48.927536],[2.336905,48.933783],[2.33544,48.940901],[2.325946,48.945588],[2.290805,48.951088],[2.292215,48.951468],[2.28833,48.958384],[2.29033,48.961708],[2.298876,48.966305],[2.305085,48.962222],[2.30894,48.963509],[2.310371,48.961745],[2.313005,48.962411],[2.322693,48.957525],[2.327919,48.959516],[2.333363,48.955318],[2.343865,48.968421],[2.353466,48.965624],[2.358985,48.972869],[2.365742,48.974028],[2.384159,48.971126],[2.396541,48.961087],[2.407356,48.956092],[2.417436,48.959524],[2.418391,48.95759],[2.427022,48.959413],[2.435771,48.955075],[2.445179,48.956741],[2.450042,48.954971],[2.459489,48.955049],[2.460718,48.958396],[2.467346,48.964306],[2.469496,48.963022],[2.47503,48.967668],[2.477798,48.966703],[2.480767,48.969813],[2.486772,48.967258],[2.490258,48.97007],[2.488321,48.971297],[2.49249,48.974586],[2.496176,48.972599],[2.497961,48.973863],[2.49579,48.975055],[2.500487,48.975435],[2.50211,48.976568],[2.500374,48.977707],[2.501877,48.978535],[2.514719,48.982231],[2.512257,48.986049],[2.517006,48.98611],[2.516923,48.987614],[2.519202,48.987843],[2.518923,48.990952],[2.522779,48.992144],[2.524412,48.996248],[2.532189,49.005238],[2.541198,49.006288],[2.547818,49.00505],[2.54926,49.003162],[2.548819,49.006704],[2.553079,49.006782],[2.553456,49.010141],[2.555387,49.010975],[2.565584,49.012332],[2.565889,49.011024],[2.569137,49.011283],[2.570726,49.007712],[2.564495,49.007316],[2.564702,49.00563],[2.570068,49.005887],[2.569068,49.003766],[2.57129,49.004239],[2.573488,49.001014],[2.571517,48.999872],[2.575961,49.000279],[2.581285,48.99053],[2.579292,48.990188],[2.581443,48.985968],[2.576646,48.984127],[2.578664,48.97918],[2.563964,48.980689],[2.56221,48.979142],[2.582275,48.958097],[2.589242,48.954008],[2.593745,48.946935],[2.595933,48.938608],[2.603314,48.937741],[2.602137,48.93495],[2.6029,48.931006],[2.602722,48.929381],[2.589338,48.922249],[2.588933,48.9172],[2.590394,48.915813],[2.589211,48.912025],[2.590733,48.911855],[2.588828,48.909473],[2.592323,48.907715],[2.584133,48.90338],[2.589148,48.898633],[2.586991,48.898035],[2.588025,48.896046],[2.586742,48.896224],[2.586982,48.893982],[2.583889,48.8948],[2.576621,48.892238],[2.572279,48.892487],[2.570172,48.890353],[2.562765,48.889714],[2.559201,48.885158],[2.56481,48.88138],[2.571414,48.879775],[2.571938,48.878213],[2.567763,48.872564],[2.570644,48.869349],[2.567611,48.865871],[2.572413,48.867938],[2.575551,48.865359],[2.588069,48.864321],[2.586965,48.860233],[2.583242,48.858367],[2.581442,48.858987],[2.583225,48.855416],[2.574174,48.853444],[2.587285,48.833542],[2.585677,48.829375],[2.58723,48.825585],[2.591422,48.827745],[2.592515,48.826801],[2.595874,48.809751],[2.591775,48.80725],[2.570025,48.814971],[2.568721,48.817959],[2.572415,48.823591],[2.561309,48.82654],[2.560553,48.828945],[2.556767,48.831642],[2.555409,48.83106],[2.549164,48.835076],[2.547445,48.834172],[2.547323,48.836107],[2.544138,48.835005],[2.538074,48.836883],[2.536667,48.838539],[2.540807,48.839363],[2.537456,48.841064],[2.534549,48.845327],[2.528762,48.843584],[2.524657,48.849144],[2.519029,48.847966],[2.515592,48.85129],[2.513513,48.850063],[2.505484,48.857212],[2.496819,48.855693],[2.494887,48.858812],[2.497113,48.859192],[2.496107,48.860663],[2.491577,48.859547],[2.491024,48.860797],[2.481592,48.861487],[2.480807,48.860055],[2.468358,48.860668],[2.466752,48.860077],[2.467409,48.856626],[2.4536,48.855325],[2.448822,48.853456],[2.44714,48.851163],[2.435233,48.853363],[2.434578,48.850827],[2.429576,48.851061],[2.429311,48.848745],[2.416341,48.849234],[2.415986,48.85136]]]}');

        $regionIdfOrg = (new Organization(self::REGION_IDF_ID))
            ->setName('Région Ile de France')
            ->setCreatedAt(new \DateTimeImmutable('2023-02-13'))
            ->setSiret('23750007900312')
            ->setCode('11')
            ->setCodeType(OrganizationCodeTypeEnum::REGION->value)
            ->setGeometry('{"type":"Polygon","coordinates":[[[1.709034,48.59525],[1.718829,48.605747],[1.714974,48.614668],[1.68711,48.611179],[1.682339,48.615626],[1.690921,48.617137],[1.685813,48.620316],[1.673483,48.613549],[1.6674,48.61698],[1.666533,48.613474],[1.655004,48.622582],[1.658336,48.62789],[1.64932,48.632292],[1.65123,48.638206],[1.638449,48.645191],[1.643142,48.651276],[1.621309,48.649665],[1.602778,48.663117],[1.601117,48.668365],[1.607999,48.672515],[1.604293,48.6754],[1.611846,48.689291],[1.591106,48.692752],[1.576365,48.702597],[1.594709,48.709145],[1.58706,48.710279],[1.620568,48.73609],[1.626753,48.748104],[1.608417,48.760732],[1.581999,48.761904],[1.587894,48.773749],[1.576741,48.782974],[1.575397,48.79088],[1.580926,48.793507],[1.577302,48.804944],[1.591555,48.814999],[1.578959,48.8297],[1.598095,48.838892],[1.577354,48.844624],[1.583854,48.860645],[1.560267,48.867666],[1.556264,48.864437],[1.54561,48.870217],[1.563429,48.890488],[1.538775,48.904712],[1.538148,48.908539],[1.545295,48.911725],[1.538411,48.922548],[1.509183,48.925298],[1.507106,48.929197],[1.511997,48.93473],[1.501397,48.941034],[1.510875,48.953586],[1.501062,48.951423],[1.495189,48.956815],[1.499819,48.960216],[1.491826,48.96463],[1.518265,48.978403],[1.507898,48.983857],[1.498427,48.979076],[1.478695,48.980681],[1.470982,48.974954],[1.461002,48.987143],[1.471735,48.989721],[1.46907,48.993216],[1.480672,49.001038],[1.480007,49.007248],[1.473882,49.009659],[1.477013,49.016824],[1.457985,49.025972],[1.458244,49.034538],[1.4463,49.04633],[1.452773,49.050369],[1.447292,49.053503],[1.461791,49.063972],[1.479703,49.051963],[1.503804,49.059783],[1.514274,49.079378],[1.507927,49.085407],[1.517852,49.079168],[1.523017,49.068205],[1.541117,49.073856],[1.557901,49.069114],[1.573049,49.078211],[1.605026,49.085171],[1.608748,49.077711],[1.623409,49.086],[1.618811,49.08751],[1.623419,49.093529],[1.617132,49.093872],[1.618427,49.096879],[1.657848,49.131941],[1.656672,49.137572],[1.652655,49.135002],[1.651421,49.138748],[1.65583,49.14847],[1.664457,49.153428],[1.660581,49.157308],[1.670241,49.169534],[1.66609,49.176748],[1.671599,49.180395],[1.669026,49.187089],[1.676892,49.202532],[1.672163,49.206457],[1.673966,49.211898],[1.703859,49.228845],[1.70939,49.241488],[1.712115,49.23302],[1.740441,49.224283],[1.734109,49.221277],[1.733682,49.210959],[1.714868,49.207444],[1.715526,49.202663],[1.725052,49.200779],[1.725879,49.194169],[1.737268,49.19479],[1.744287,49.183307],[1.740102,49.18081],[1.754858,49.174591],[1.776392,49.184883],[1.784799,49.185816],[1.790945,49.179969],[1.795474,49.185263],[1.803075,49.185108],[1.800259,49.180409],[1.813082,49.178339],[1.811784,49.173972],[1.826821,49.17965],[1.83585,49.176413],[1.836422,49.164497],[1.844683,49.170659],[1.876137,49.175053],[1.882953,49.162576],[1.887438,49.16302],[1.931454,49.175031],[1.935401,49.169589],[1.949742,49.170478],[1.97358,49.183861],[2.00268,49.176019],[2.021778,49.188808],[2.033566,49.188102],[2.047324,49.19869],[2.072507,49.204184],[2.080737,49.210715],[2.078967,49.205918],[2.090932,49.208642],[2.092653,49.19532],[2.089048,49.193784],[2.097719,49.189173],[2.109116,49.190925],[2.116355,49.186906],[2.127979,49.193588],[2.13522,49.191181],[2.136476,49.179005],[2.147284,49.18805],[2.16451,49.179549],[2.158034,49.17263],[2.16879,49.163947],[2.18225,49.170381],[2.176093,49.176032],[2.195306,49.173125],[2.218646,49.180685],[2.235385,49.166994],[2.216843,49.153893],[2.241315,49.151589],[2.286479,49.159873],[2.288806,49.17071],[2.299516,49.175964],[2.301273,49.184041],[2.310801,49.186746],[2.321595,49.184372],[2.359343,49.147329],[2.373072,49.159451],[2.3912,49.149314],[2.413122,49.152418],[2.440304,49.146137],[2.435477,49.134049],[2.458873,49.140902],[2.461286,49.136142],[2.47154,49.135404],[2.477511,49.13098],[2.475313,49.128462],[2.50297,49.117769],[2.491218,49.11142],[2.490068,49.106246],[2.531263,49.099458],[2.532963,49.119509],[2.54207,49.122248],[2.537727,49.117677],[2.547127,49.116379],[2.553315,49.124562],[2.556999,49.121851],[2.551751,49.112426],[2.557933,49.09872],[2.578584,49.091982],[2.583559,49.079825],[2.597396,49.081549],[2.610051,49.094969],[2.62209,49.094797],[2.633912,49.1086],[2.641179,49.099087],[2.650478,49.10087],[2.677533,49.08819],[2.695039,49.074999],[2.689636,49.067756],[2.695802,49.064555],[2.706185,49.065309],[2.724182,49.080672],[2.734606,49.060526],[2.753998,49.060814],[2.777875,49.070264],[2.787282,49.075527],[2.768715,49.083338],[2.789125,49.082999],[2.783868,49.088904],[2.809213,49.097533],[2.822395,49.086229],[2.845081,49.084834],[2.854636,49.070435],[2.870163,49.070099],[2.887548,49.079497],[2.895711,49.077253],[2.901553,49.085368],[2.923288,49.077592],[2.933456,49.081602],[2.941492,49.077256],[2.945817,49.088318],[2.956487,49.085285],[2.968013,49.091524],[2.975082,49.074188],[2.982852,49.071094],[2.988299,49.072042],[2.991183,49.083996],[3.000853,49.089458],[3.00604,49.0864],[3.00854,49.091608],[3.031697,49.085662],[3.032891,49.088935],[3.066139,49.085191],[3.055877,49.100841],[3.07063,49.117886],[3.079589,49.111971],[3.136916,49.107683],[3.14768,49.10098],[3.165232,49.099863],[3.153421,49.083354],[3.176423,49.070066],[3.182151,49.05194],[3.190735,49.051283],[3.189907,49.046029],[3.180689,49.044637],[3.183035,49.041111],[3.177153,49.030135],[3.16115,49.024303],[3.168585,49.016994],[3.167452,49.012458],[3.198218,49.008843],[3.196093,49.00481],[3.207269,49.000703],[3.209439,48.993971],[3.229411,48.98858],[3.227013,48.982487],[3.231156,48.976945],[3.250168,48.973921],[3.264926,48.938788],[3.284527,48.940531],[3.304411,48.94878],[3.312719,48.93351],[3.31326,48.92125],[3.330294,48.908707],[3.346838,48.91709],[3.359742,48.91802],[3.367109,48.922308],[3.367999,48.928304],[3.376378,48.909486],[3.370663,48.905161],[3.369284,48.893817],[3.383762,48.889058],[3.381371,48.871551],[3.405936,48.876082],[3.39974,48.866322],[3.40428,48.864027],[3.424159,48.867478],[3.433003,48.859573],[3.446598,48.860194],[3.452128,48.856144],[3.445822,48.84327],[3.462762,48.837213],[3.470222,48.851178],[3.485019,48.851845],[3.491304,48.834617],[3.485182,48.825236],[3.487032,48.815116],[3.480836,48.812183],[3.469894,48.8211],[3.448053,48.811023],[3.416336,48.81797],[3.405258,48.810247],[3.410477,48.803947],[3.423101,48.800686],[3.442092,48.803733],[3.442806,48.785856],[3.429528,48.779435],[3.424445,48.784883],[3.40823,48.784161],[3.395985,48.758583],[3.408246,48.752383],[3.428532,48.758432],[3.436508,48.753237],[3.438504,48.739102],[3.469715,48.737883],[3.463879,48.707239],[3.47779,48.697876],[3.472421,48.697811],[3.471759,48.685893],[3.447647,48.677207],[3.442699,48.672332],[3.446365,48.666899],[3.440556,48.663651],[3.460573,48.653044],[3.450864,48.634675],[3.476122,48.637102],[3.492256,48.647451],[3.513168,48.643496],[3.533603,48.646518],[3.5176,48.637038],[3.519286,48.633084],[3.538556,48.631463],[3.559027,48.617719],[3.502989,48.604638],[3.516296,48.58984],[3.493484,48.590065],[3.485308,48.580344],[3.465353,48.570472],[3.473987,48.564662],[3.476519,48.551806],[3.486179,48.546994],[3.479543,48.545344],[3.481751,48.541453],[3.456982,48.529585],[3.438971,48.528086],[3.414227,48.533476],[3.405371,48.527852],[3.423932,48.514251],[3.435231,48.49687],[3.434639,48.490252],[3.421212,48.492002],[3.383694,48.479626],[3.391146,48.470445],[3.39965,48.468066],[3.394161,48.463124],[3.407444,48.44926],[3.401603,48.435716],[3.400629,48.435089],[3.3987,48.436387],[3.394596,48.436267],[3.39729,48.434055],[3.391674,48.42327],[3.411856,48.421422],[3.403192,48.414761],[3.422325,48.416136],[3.414736,48.390188],[3.402015,48.389641],[3.38334,48.400053],[3.367229,48.394252],[3.362857,48.382452],[3.365175,48.372273],[3.356674,48.370993],[3.356693,48.378504],[3.351148,48.378744],[3.348345,48.373334],[3.33546,48.370187],[3.312351,48.377244],[3.305205,48.372952],[3.299602,48.37891],[3.289659,48.375814],[3.291269,48.380042],[3.282669,48.381624],[3.282485,48.377441],[3.268045,48.377837],[3.254468,48.365098],[3.223327,48.370468],[3.200233,48.363642],[3.171566,48.377423],[3.166912,48.370896],[3.152429,48.371436],[3.146587,48.365566],[3.139819,48.372571],[3.121936,48.368588],[3.103356,48.349359],[3.098663,48.357669],[3.050282,48.359999],[3.037063,48.342665],[3.036665,48.334946],[3.04337,48.330655],[3.028064,48.321605],[3.014648,48.306285],[3.027523,48.302836],[3.018597,48.294958],[3.029614,48.285777],[3.024731,48.276028],[3.045469,48.27074],[3.042684,48.262404],[3.047354,48.249695],[3.031423,48.248849],[3.018833,48.235115],[3.023092,48.230448],[3.004462,48.207028],[2.973239,48.205618],[2.970965,48.19421],[2.954721,48.19246],[2.936957,48.183148],[2.936624,48.163456],[2.867626,48.156615],[2.849978,48.141334],[2.819326,48.129733],[2.800782,48.131632],[2.796847,48.141801],[2.801201,48.149648],[2.798553,48.152428],[2.808913,48.15878],[2.810261,48.166101],[2.78,48.167622],[2.77153,48.161619],[2.767558,48.165026],[2.755208,48.160202],[2.736695,48.166148],[2.753566,48.153199],[2.755623,48.145887],[2.720566,48.136963],[2.705507,48.12312],[2.677037,48.125817],[2.664442,48.120077],[2.639201,48.138905],[2.603036,48.131606],[2.574718,48.1313],[2.570622,48.140919],[2.559267,48.141752],[2.537913,48.140657],[2.521184,48.125048],[2.476255,48.129829],[2.464922,48.129351],[2.454346,48.122435],[2.441775,48.124769],[2.443959,48.131584],[2.459915,48.136867],[2.467457,48.148272],[2.474625,48.149004],[2.471887,48.154523],[2.478398,48.155007],[2.483156,48.164601],[2.505954,48.156506],[2.516773,48.166761],[2.512113,48.170754],[2.515572,48.174575],[2.50715,48.179759],[2.5179,48.190287],[2.512754,48.193089],[2.522982,48.194861],[2.51753,48.214433],[2.514052,48.21448],[2.513321,48.224505],[2.518519,48.22831],[2.506634,48.225814],[2.506272,48.238629],[2.484174,48.238907],[2.476092,48.244643],[2.478794,48.24983],[2.468696,48.255334],[2.450113,48.249921],[2.446349,48.2553],[2.432279,48.254862],[2.42351,48.260217],[2.417575,48.278442],[2.423438,48.294419],[2.402969,48.320644],[2.397269,48.313214],[2.358263,48.30843],[2.34879,48.316577],[2.341904,48.316096],[2.334997,48.327549],[2.328115,48.326454],[2.329271,48.332808],[2.317147,48.331664],[2.295602,48.307907],[2.266807,48.314711],[2.264053,48.307602],[2.245215,48.298215],[2.252357,48.316627],[2.237996,48.316263],[2.246243,48.331182],[2.237846,48.332221],[2.231934,48.327682],[2.223927,48.336435],[2.215204,48.334282],[2.204109,48.344912],[2.202866,48.338756],[2.187993,48.332409],[2.182019,48.324077],[2.185173,48.321602],[2.180919,48.311454],[2.171622,48.316045],[2.169184,48.312753],[2.152065,48.316104],[2.15561,48.304059],[2.160867,48.304696],[2.163885,48.298378],[2.110394,48.29701],[2.113655,48.307423],[2.105404,48.307702],[2.089426,48.294777],[2.052655,48.295431],[2.051815,48.290349],[2.040787,48.284559],[2.024863,48.289203],[2.011419,48.284922],[1.972252,48.288219],[1.98219,48.29517],[1.965243,48.295495],[1.958831,48.306618],[1.973492,48.315952],[1.978983,48.312871],[1.980769,48.318729],[1.974258,48.323319],[1.982366,48.328472],[1.96848,48.341422],[1.974927,48.344744],[1.972828,48.355924],[1.989167,48.363602],[1.977553,48.368825],[1.980008,48.378436],[1.966445,48.381678],[1.978188,48.401829],[1.928327,48.406144],[1.932743,48.410893],[1.925818,48.412789],[1.927473,48.416584],[1.939736,48.419824],[1.936314,48.434546],[1.942874,48.441144],[1.932988,48.442164],[1.929495,48.457131],[1.922016,48.457709],[1.920949,48.447951],[1.906609,48.445566],[1.904331,48.438559],[1.870785,48.439832],[1.857482,48.446838],[1.844879,48.446251],[1.83673,48.467085],[1.801288,48.466016],[1.803341,48.472522],[1.791287,48.481125],[1.796546,48.484608],[1.7854,48.490129],[1.789018,48.500151],[1.782889,48.500264],[1.775593,48.513901],[1.77555,48.527678],[1.787543,48.553816],[1.777972,48.552644],[1.768348,48.558767],[1.762563,48.565022],[1.765318,48.569355],[1.757454,48.57509],[1.727001,48.572603],[1.709245,48.578022],[1.710008,48.581507],[1.701852,48.585048],[1.709034,48.59525]]]}');

        $saintOuenOrg = (new Organization(self::SAINT_OUEN_ID))
            ->setName('Commune de Saint Ouen sur Seine')
            ->setCreatedAt(new \DateTimeImmutable('2023-06-24'))
            ->setSiret('21930070400018')
            ->setCode('93070')
            ->setCodeType('insee')
            ->setGeometry('{"type":"Polygon","coordinates":[[[2.330147,48.901044],[2.326534,48.900977],[2.325836,48.900954],[2.32327,48.900813],[2.322174,48.900794],[2.322073,48.900828],[2.320434,48.900776],[2.319887,48.90046],[2.319609,48.901355],[2.320245,48.902921],[2.320462,48.903487],[2.320585,48.903747],[2.320866,48.904612],[2.320851,48.904666],[2.320205,48.905359],[2.319287,48.906413],[2.318541,48.907231],[2.317999,48.9079],[2.318361,48.907977],[2.318323,48.908052],[2.3168,48.909598],[2.316269,48.910157],[2.316596,48.910274],[2.316012,48.911052],[2.315584,48.911597],[2.314501,48.913087],[2.313768,48.914045],[2.314061,48.914152],[2.316356,48.914875],[2.317288,48.915162],[2.31884,48.915626],[2.319819,48.915937],[2.320987,48.916222],[2.32181,48.916408],[2.323114,48.916729],[2.324468,48.917079],[2.32629,48.917649],[2.32748,48.918085],[2.328416,48.918479],[2.329506,48.919188],[2.330123,48.919678],[2.330843,48.920427],[2.331682,48.92134],[2.332578,48.922455],[2.333246,48.923206],[2.33412,48.922666],[2.336823,48.921103],[2.336993,48.92113],[2.337257,48.920991],[2.337136,48.920926],[2.339153,48.920022],[2.33912,48.919995],[2.340011,48.919618],[2.339621,48.918976],[2.339244,48.918262],[2.33911,48.918067],[2.338118,48.915947],[2.337802,48.915217],[2.33851,48.914917],[2.339081,48.914746],[2.339601,48.914627],[2.340078,48.914545],[2.340527,48.914487],[2.343225,48.914179],[2.344674,48.914028],[2.345229,48.914019],[2.34572,48.914074],[2.347058,48.914382],[2.348254,48.911395],[2.348398,48.910991],[2.348885,48.911017],[2.349757,48.911026],[2.350838,48.910948],[2.351054,48.910718],[2.351269,48.910322],[2.351353,48.910066],[2.351477,48.909378],[2.351461,48.909275],[2.351644,48.908347],[2.351602,48.908129],[2.351234,48.907588],[2.35119,48.907462],[2.35122,48.907183],[2.351354,48.905335],[2.35143,48.90453],[2.351543,48.903686],[2.3516,48.903602],[2.351691,48.903079],[2.351825,48.902523],[2.351839,48.902356],[2.351984,48.901485],[2.344985,48.901345],[2.339771,48.901245],[2.337938,48.901208],[2.331996,48.901078],[2.330147,48.901044]]]}');

        $manager->persist($seineSaintDenisOrg);
        $manager->persist($regionIdfOrg);
        $manager->persist($saintOuenOrg);
        $manager->flush();

        $this->addReference('seineSaintDenisOrg', $seineSaintDenisOrg);
        $this->addReference('regionIdfOrg', $regionIdfOrg);
        $this->addReference('saintOuenOrg', $saintOuenOrg);
    }
}
