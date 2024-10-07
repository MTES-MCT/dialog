<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\IntegrationReport;

use App\Infrastructure\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\IntegrationReport\RecordTypeEnum;
use App\Infrastructure\IntegrationReport\ReportFormatter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReportFormatterTest extends KernelTestCase
{
    private $translator;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->translator = $container->get(TranslatorInterface::class);
    }

    public function testFormatEmpty(): void
    {
        $reportFormatter = new ReportFormatter($this->translator);

        $records = [];

        $expectedResult = "Rapport d'intégration
======================

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
        $reportFormatter = new ReportFormatter($this->translator);

        $records = [
            // Out of order to test the grouping of records by type
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_START_TIME->value, 'value' => 'start']],
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_ELAPSED_SECONDS->value, 'value' => 90]],
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_INTEGRATION_NAME->value, 'value' => 'Litteralis TEST']],
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_ORGANIZATION->value, 'value' => ['uuid' => '<uuid>', 'name' => 'Test org']]],
            [RecordTypeEnum::COUNT->value, [RecordTypeEnum::COUNT->value => 'example.total_features', 'value' => '10']],
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => 'example.fact1', 'value' => 'abc']],
            [RecordTypeEnum::WARNING->value, [RecordTypeEnum::WARNING->value => 'example.missing_geometry', CommonRecordEnum::ATTR_REGULATION_ID->value => 'arrete4']],
            [RecordTypeEnum::NOTICE->value, [RecordTypeEnum::NOTICE->value => 'example.no_measures_found', CommonRecordEnum::ATTR_REGULATION_ID->value => 'arrete3']],
            [
                RecordTypeEnum::ERROR->value,
                [
                    RecordTypeEnum::ERROR->value => 'example.max_speed_value_missing',
                    CommonRecordEnum::ATTR_REGULATION_ID->value => 'arrete1',
                    CommonRecordEnum::ATTR_URL->value => 'http://testserver/arrete1',
                ],
            ],
            [RecordTypeEnum::WARNING->value, [RecordTypeEnum::WARNING->value => 'example.missing_geometry', CommonRecordEnum::ATTR_REGULATION_ID->value => 'arrete2']],
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_END_TIME->value, 'value' => 'end']],
        ];

        $expectedResult = "Rapport d'intégration
======================

Informations d'exécution
-------------------------

Date et heure de début : start
Temps d'exécution : 1 min 30 s
Nom de l'intégration : Litteralis TEST
Organisation cible : uuid: <uuid> ; name: Test org
integration.report.fact.example.fact1 : abc
Date et heure de fin : end

Décomptes
----------

integration.report.count.example.total_features : 10

Erreurs
-------

integration.report.error.example.max_speed_value_missing : 1 (dans 1 arrêtés)
  Arrêtés :
    arrete1 (http://testserver/arrete1)


Avertissements
--------------

integration.report.warning.example.missing_geometry : 2 (dans 2 arrêtés)
  Arrêtés :
    arrete4
    arrete2


Remarques
---------

integration.report.notice.example.no_measures_found : 1 (dans 1 arrêtés)
  Arrêtés :
    arrete3

";

        $this->assertSame($expectedResult, $reportFormatter->format($records));
    }
}
