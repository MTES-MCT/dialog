<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\IntegrationReport;

enum CommonRecordEnum: string
{
    case ATTR_REGULATION_ID = 'regulationId';
    case ATTR_URL = 'url';
    case ATTR_DETAILS = 'details';

    case FACT_INTEGRATION_NAME = 'common.integration_name';
    case FACT_ORGANIZATION = 'common.organization';
    case FACT_START_TIME = 'common.start_time';
    case FACT_END_TIME = 'common.end_time';
    case FACT_ELAPSED_SECONDS = 'common.elapsed_seconds';
}
