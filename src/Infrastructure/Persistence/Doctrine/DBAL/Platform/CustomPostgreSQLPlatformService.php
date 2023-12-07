<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL\Platform;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;

// Credit: https://medium.com/yousign-engineering-product/ignore-custom-indexes-on-doctrine-dbal-b5131dd22071

class CustomPostgreSQLPlatformService extends \Doctrine\DBAL\Platforms\PostgreSQLPlatform
{
    public function createSchemaManager(Connection $connection): PostgreSQLSchemaManager
    {
        return new _CustomPostgreSQLSchemaManager($connection, $this);
    }
}

class _CustomPostgreSQLSchemaManager extends PostgreSQLSchemaManager
{
    private const INDEXES_TO_IGNORE = [
        'idx_fr_city_name_departement',
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
