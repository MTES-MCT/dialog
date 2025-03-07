<?php

declare(strict_types=1);

namespace App\Domain\Organization\Enum;

enum OrganizationCodeTypeEnum: string
{
    case INSEE = 'insee';
    case EPCI = 'epci';
    case REGION = 'region';
    case DEPARTMENT = 'departement';
}
