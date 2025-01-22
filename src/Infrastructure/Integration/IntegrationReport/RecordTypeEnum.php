<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\IntegrationReport;

enum RecordTypeEnum: string
{
    // Un COUNT est un nombre total de quelque chose
    case COUNT = 'count';

    // Si une ERROR est rencontrée, l'arrêté ne devrait pas être importé au risque de ne pas être fidèle aux données source.
    case ERROR = 'error';

    // Si un WARNING est rencontré, des incohérences ont été détectées dans la donnée source, mais elles
    // n'empêchent pas d'importer l'arrêté.
    case WARNING = 'warning';

    // Une NOTICE fournit toute autre information complémentaire qui n'a pas d'incidence
    // sur ce qui va être importé.
    case NOTICE = 'notice';

    // Un FACT est une donnée quantitative ou textuelle que l'on souhaite afficher dans le rapport
    case FACT = 'fact';
}
