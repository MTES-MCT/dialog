<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Litteralis;

use App\Infrastructure\Litteralis\LitteralisReporter;
use App\Infrastructure\Litteralis\LitteralisReportFormatter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *  @group only
 */
final class LitteralisReportFormatterTest extends KernelTestCase
{
    private $translator;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->translator = $container->get(TranslatorInterface::class);
    }

    public function testFormatEmpty(): void
    {
        $reportFormatter = new LitteralisReportFormatter($this->translator);

        $records = [];

        $expectedResult = "Intégration Litteralis
=======================

Informations d'exécution
-------------------------


Décomptes
----------


Erreurs
-------


Avertissements
--------------


Remarques
---------

";

        $this->assertSame($expectedResult, $reportFormatter->format($records));
    }

    public function testFormat(): void
    {
        $reportFormatter = new LitteralisReportFormatter($this->translator);

        $records = [
            // Out of order to test the grouping of records
            [LitteralisReporter::FACT, [LitteralisReporter::FACT => 'elapsed_seconds', 'value' => 90]],
            [LitteralisReporter::COUNT, [LitteralisReporter::COUNT => LitteralisReporter::COUNT_TOTAL_FEATURES, 'value' => '10']],
            [LitteralisReporter::FACT, [LitteralisReporter::FACT => 'fact1', 'value' => 'abc']],
            [LitteralisReporter::WARNING, [LitteralisReporter::WARNING => LitteralisReporter::WARNING_MISSING_GEOMETRY, 'arretesrcid' => 'arrete4']],
            [LitteralisReporter::NOTICE, [LitteralisReporter::NOTICE => LitteralisReporter::NOTICE_NO_MEASURES_FOUND, 'arretesrcid' => 'arrete3']],
            [LitteralisReporter::ERROR, [LitteralisReporter::ERROR => LitteralisReporter::ERROR_MAX_SPEED_VALUE_MISSING, 'arretesrcid' => 'arrete1', 'shorturl' => 'http://testserver/arrete1']],
            [LitteralisReporter::WARNING, [LitteralisReporter::WARNING => LitteralisReporter::WARNING_MISSING_GEOMETRY, 'arretesrcid' => 'arrete2']],
            [LitteralisReporter::WARNING, [LitteralisReporter::WARNING => LitteralisReporter::WARNING_MISSING_GEOMETRY]],
        ];

        $expectedResult = "Intégration Litteralis
=======================

Informations d'exécution
-------------------------

Temps d'exécution : 1 min 30 s
litteralis.report.fact.fact1 : abc

Décomptes
----------

Nombre total d'emprises dans Litteralis pour cette organisation : 10

Erreurs
-------

Emprises avec limite de vitesse manquante : 1 (dans 1 arrêtés)
  Arrêtés :
    arrete1 (http://testserver/arrete1)


Avertissements
--------------

Emprises sans géométrie : 3 (dans 3 arrêtés)
  Arrêtés :
    arrete4
    arrete2
    <unknown>


Remarques
---------

Arrêtés sans aucune mesure connue de DiaLog : 1 (dans 1 arrêtés)
  Arrêtés :
    arrete3

";

        $this->assertSame($expectedResult, $reportFormatter->format($records));
    }
}
