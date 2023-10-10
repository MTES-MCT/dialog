<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationOrderRecordSourceEnum: string
{
    case DIALOG = 'dialog';
    case EUDONET_PARIS = 'eudonet_paris';
    case BAC_IDF = 'bac_idf';
}
