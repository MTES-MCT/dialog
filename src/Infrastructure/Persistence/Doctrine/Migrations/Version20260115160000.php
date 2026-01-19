<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Convert serialized PHP arrays to JSON format for Doctrine ORM 3 compatibility.
 */
final class Version20260115160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert serialized PHP arrays to JSON format for Doctrine ORM 3 compatibility';
    }

    public function up(Schema $schema): void
    {
        // Convert user.roles from PHP serialize to JSON (user is a reserved word in PostgreSQL)
        $this->convertSerializedToJson('"user"', 'roles');

        // Convert organizations_users.roles from PHP serialize to JSON
        $this->convertSerializedToJson('organizations_users', 'roles');

        // Convert vehicle_set columns from PHP serialize to JSON
        $this->convertSerializedToJson('vehicle_set', 'restricted_types');
        $this->convertSerializedToJson('vehicle_set', 'critair_types');
        $this->convertSerializedToJson('vehicle_set', 'exempted_types');

        // Convert dailyrange.applicable_days from PHP serialize to JSON
        $this->convertSerializedToJson('dailyrange', 'applicable_days');
    }

    public function down(Schema $schema): void
    {
        // Cannot easily revert JSON to PHP serialize format
    }

    private function convertSerializedToJson(string $table, string $column): void
    {
        $connection = $this->connection;

        // Fetch all rows with serialized data
        $rows = $connection->fetchAllAssociative(
            \sprintf('SELECT uuid, %s FROM %s WHERE %s IS NOT NULL', $column, $table, $column),
        );

        foreach ($rows as $row) {
            $value = $row[$column];

            // Skip if already valid JSON
            if ($this->isJson($value)) {
                continue;
            }

            // Try to unserialize PHP array
            $unserialized = @unserialize($value);
            if ($unserialized === false && $value !== 'b:0;') {
                // Not a valid serialized string, skip
                continue;
            }

            // Convert to JSON
            $jsonValue = json_encode($unserialized ?: []);

            // Update the row
            $connection->executeStatement(
                \sprintf('UPDATE %s SET %s = :json WHERE uuid = :uuid', $table, $column),
                ['json' => $jsonValue, 'uuid' => $row['uuid']],
            );
        }
    }

    private function isJson(string $string): bool
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
