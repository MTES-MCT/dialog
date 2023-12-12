<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;

// This class and the custom SchemaManager customize the execution of doctrine:schema:validate.
// We ignore custom indexes that cannot be defined in Doctrine mapping, otherwise the doctrine:schema:validate command
// would report the database as not in sync with the mapping, and fail.
// Credit: https://medium.com/yousign-engineering-product/ignore-custom-indexes-on-doctrine-dbal-b5131dd22071

class CustomPostgreSQLPlatformService extends \Doctrine\DBAL\Platforms\PostgreSQLPlatform
{
    public function createSchemaManager(Connection $connection): PostgreSQLSchemaManager
    {
        return new CustomPostgreSQLSchemaManager($connection, $this);
    }
}
