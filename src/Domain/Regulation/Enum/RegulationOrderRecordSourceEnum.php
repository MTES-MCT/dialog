<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum RegulationOrderRecordSourceEnum: string
{
    public const DIALOG = 'dialog';
    public const EUDONET_PARIS = 'eudonet_paris';
}
