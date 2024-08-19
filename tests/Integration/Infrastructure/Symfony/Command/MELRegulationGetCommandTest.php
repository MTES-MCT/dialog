<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\Command\MELRegulationGetCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class MELRegulationGetCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $command = $container->get(MELRegulationGetCommand::class);
        $commandTester = new CommandTester($command);

        // Data comes from mock
        $commandTester->execute(['id' => '24/0194']);
        $commandTester->assertCommandIsSuccessful($commandTester->getDisplay());
        $this->assertSame(
            '[{"type":"Feature","id":"litteralis.494385","geometry":{"type":"Polygon","coordinates":[[[3.0513837908,50.6878190776],[3.0513932129,50.6878103763],[3.0514542429,50.6877367309],[3.0514623653,50.6877165423],[3.0514664133,50.6875545762],[3.0514588553,50.6875336587],[3.0513090598,50.6873449852],[3.0511829366,50.6873853954],[3.0513248976,50.6875642016],[3.0513213931,50.6877044074],[3.051272117,50.6877638691],[3.0511091969,50.6878817504],[3.051215348,50.687940955],[3.0513837908,50.6878190776]]]},"geometry_name":"geometry","properties":{"idemprise":494385,"idarrete":850504671,"shorturl":"https:\/\/dl.sogelink.fr\/?0dbjHha7","arretesrcid":"24\/0194","collectivitelibelle":"VILLE DE WAMBRECHIES","collectiviteid":null,"collectiviteagencelibelle":"Mairie de Wambrechies","collectiviteagenceid":173214,"documenttype":"ARRETE TEMPORAIRE","arretedebut":"2024-03-18T01:00:00Z","arretefin":"2024-03-19T01:00:00Z","empriseno":1,"emprisetype":"CIRCULATION","emprisedebut":"2024-03-18T01:00:00Z","emprisefin":"2024-03-19T01:00:00Z","mesures":"SOGELINK - Circulation interdite","localisations":"AVENUE DES CH\u00c2TEAUX, DU 1 JUSQU\'\u00c0 LA PLACE DE LA DISTILLERIE;PLACE DE LA DISTILLERIE, DE LA RUE DE LA DISTILLERIE JUSQU\'\u00c0 LA RUE DU G\u00c9N\u00c9RAL LECLERC","idagence":173214,"fournisseur":"LIPRIME","publicationinternet":true,"emetteurlibelle":"SYLVAGREG","emetteurid":null,"categoriesmodele":"Travaux","nommodele":"SOGELINK - AC2 - Arr\u00eat\u00e9 temporaire travaux","parametresarrete":"Date de r\u00e9ception de la demande : 15\/03\/2024 00:00:00 ; Date de d\u00e9but de l\'arr\u00eat\u00e9 : 18\/03\/2024 00:00:00 ; Date de fin de l\'arr\u00eat\u00e9 : 18\/03\/2024 00:00:00 ; Description des travaux : de chargement d\'engin de chantier ; ajout annexe : N ; charg\u00e9 de MEP de la signalisation : Le demandeur de l\'acte","parametresemprise":"Dates de l\'emprise : Du 18\/03\/2024 00:00:00 au 18\/03\/2024 00:00:00 ; jours et horaires : de 10h00 \u00e0 15h00","parametresmesures":"","datecreation":"2024-03-18T08:30:51.050Z","datemodification":"2024-03-18T08:30:52.097Z"},"bbox":[3.0511091969,50.6873449852,3.0514664133,50.687940955]},{"type":"Feature","id":"litteralis.494386","geometry":{"type":"Polygon","coordinates":[[[3.0515350354,50.6873624811],[3.0514828279,50.687353765],[3.0513485752,50.6873653595],[3.0513580895,50.6874098183],[3.0512925694,50.6873930649],[3.0512512332,50.6874583044],[3.0512492767,50.6874883116],[3.0512834341,50.6875584904],[3.0513276123,50.6875875929],[3.0514478619,50.6876142861],[3.0514711604,50.6875719298],[3.0515013251,50.6876124997],[3.0515893694,50.6875860814],[3.0516250989,50.6875616622],[3.0516587976,50.6875061768],[3.0516626551,50.687482978],[3.0516503171,50.6874337024],[3.0515805655,50.6874407505],[3.0516232585,50.6874050078],[3.0515350354,50.6873624811]],[[3.0514082811,50.6874507641],[3.051472916,50.687445182],[3.0515152707,50.6874655985],[3.0515201648,50.6874851451],[3.0515034842,50.6875126099],[3.0514671515,50.6875235118],[3.0514078584,50.6875103498],[3.0513916401,50.6874770283],[3.0514082811,50.6874507641]]]},"geometry_name":"geometry","properties":{"idemprise":494386,"idarrete":850504671,"shorturl":"https:\/\/dl.sogelink.fr\/?0dbjHha7","arretesrcid":"24\/0194","collectivitelibelle":"VILLE DE WAMBRECHIES","collectiviteid":null,"collectiviteagencelibelle":"Mairie de Wambrechies","collectiviteagenceid":173214,"documenttype":"ARRETE TEMPORAIRE","arretedebut":"2024-03-18T01:00:00Z","arretefin":"2024-03-19T01:00:00Z","empriseno":2,"emprisetype":"CIRCULATION","emprisedebut":"2024-03-18T01:00:00Z","emprisefin":"2024-03-19T01:00:00Z","mesures":"SOGELINK - Circulation interdite","localisations":"PLACE DE LA DISTILLERIE, DE LA RUE DE LA DISTILLERIE JUSQU\'\u00c0 LA RUE DU G\u00c9N\u00c9RAL LECLERC","idagence":173214,"fournisseur":"LIPRIME","publicationinternet":true,"emetteurlibelle":"SYLVAGREG","emetteurid":null,"categoriesmodele":"Travaux","nommodele":"SOGELINK - AC2 - Arr\u00eat\u00e9 temporaire travaux","parametresarrete":"Date de r\u00e9ception de la demande : 15\/03\/2024 00:00:00 ; Date de d\u00e9but de l\'arr\u00eat\u00e9 : 18\/03\/2024 00:00:00 ; Date de fin de l\'arr\u00eat\u00e9 : 18\/03\/2024 00:00:00 ; Description des travaux : de chargement d\'engin de chantier ; ajout annexe : N ; charg\u00e9 de MEP de la signalisation : Le demandeur de l\'acte","parametresemprise":"Dates de l\'emprise : Du 18\/03\/2024 00:00:00 au 18\/03\/2024 00:00:00 ; jours et horaires : de 10h00 \u00e0 15h00","parametresmesures":"SOGELINK - Circulation interdite | v\u00e9hicules concern\u00e9s : bus de transport en commun,poids lourds","datecreation":"2024-03-18T08:30:51.050Z","datemodification":"2024-03-18T08:30:52.098Z"},"bbox":[3.0512492767,50.687353765,3.0516626551,50.6876142861]}]' . PHP_EOL,
            $commandTester->getDisplay(),
        );
    }
}
