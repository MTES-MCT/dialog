<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL;

use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;

class CustomPostgreSQLSchemaManager extends PostgreSQLSchemaManager
{
    private const INDEXES_TO_IGNORE = [
        // Add custom index here if it triggers error 'The database schema is not in sync with the current mapping file'
        'idx_fr_city_name_departement',
        'idx_regulation_order_identifier',
    ];

    protected function _getPortableTableIndexesList($tableIndexes, $tableName = null): array
    {
        $indexes = parent::_getPortableTableIndexesList($tableIndexes, $tableName);

        foreach (self::INDEXES_TO_IGNORE as $index) {
            if (isset($indexes[$index])) {
                unset($indexes[$index]);
            }
        }

        return $indexes;
    }
}
