<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationOrderRecordSourceEnum: string
{
    case DIALOG = 'dialog';
    case EUDONET_PARIS = 'eudonet_paris';
    case BAC_IDF = 'bacidf';
    case JOP = 'jop';
    case LITTERALIS = 'litteralis';
    case API = 'api';
}
