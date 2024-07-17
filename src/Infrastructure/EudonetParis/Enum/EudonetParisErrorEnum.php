<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis\Enum;

enum EudonetParisErrorEnum: string
{
    case NO_MEASURES_FOUND = 'no_measures_found';
    case MEASURE_ERRORS = 'measure_errors';
    case PARSING_FAILED = 'parsing_failed';
    case MEASURE_MAY_CONTAIN_DATES = 'measure_may_contain_dates';
    case VALUE_DOES_NOT_MATCH_PATTERN = 'value_does_not_match_pattern';
    case UNSUPPORTED_LOCATION_FIELDSET = 'unsupported_location_fieldset';
    case NO_LOCATIONS_GATHERED = 'no_locations_gathered';
}
