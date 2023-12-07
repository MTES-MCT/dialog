<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;

class CustomPostgreSQLPlatformService extends \Doctrine\DBAL\Platforms\PostgreSQLPlatform
{
    public function createSchemaManager(Connection $connection): PostgreSQLSchemaManager
    {
        return new CustomPostgreSQLSchemaManager($connection, $this);
    }
}
