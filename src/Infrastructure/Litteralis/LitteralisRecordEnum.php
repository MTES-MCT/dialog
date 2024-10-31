<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

enum LitteralisRecordEnum: string
{
    case COUNT_TOTAL_FEATURES = 'litteralis.total_features';
    case COUNT_MATCHING_FEATURES = 'litteralis.matching_features';
    case COUNT_EXTRACTED_FEATURES = 'litteralis.extracted_features';
    case COUNT_IMPORTED_FEATURES = 'litteralis.imported_features';

    case ERROR_MEASURE_PARAMETER_INCONSISTENT_NUMBER = 'litteralis.measure_parameter_inconsistent_number';
    case ERROR_MAX_SPEED_VALUE_INVALID = 'litteralis.max_speed_value_invalid';
    case ERROR_MAX_SPEED_VALUE_MISSING = 'litteralis.max_speed_value_missing';
    case ERROR_DATE_PARSING_FAILED = 'litteralis.date_parsing_failed';
    case ERROR_PERIOD_UNPARSABLE = 'litteralis.period_unparsable';
    case ERROR_IMPORT_COMMAND_FAILED = 'litteralis.import_command_failed';

    case WARNING_MISSING_GEOMETRY = 'litteralis.missing_geometry';

    case NOTICE_UNSUPPORTED_MEASURE = 'litteralis.unsupported_measure';
    case NOTICE_NO_MEASURES_FOUND = 'litteralis.no_measures_found';
}
