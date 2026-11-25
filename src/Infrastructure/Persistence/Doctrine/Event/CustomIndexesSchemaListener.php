<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Event;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

// Some indexes cannot be defined in the Doctrine mapping (e.g. expression indexes created in migrations).
// DBAL introspects them as plain indexes on their non-expression columns, so we declare them the same way
// in the target schema to keep doctrine:schema:validate and doctrine:migrations:diff in sync.
#[AsDoctrineListener(event: ToolEvents::postGenerateSchema, connection: 'default')]
final class CustomIndexesSchemaListener
{
    private const CUSTOM_INDEXES = [
        // Real definition: CREATE INDEX idx_fr_city_name_departement ON fr_city USING btree (lower((name)::text), departement)
        'fr_city' => ['idx_fr_city_name_departement' => ['departement']],
    ];

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        foreach (self::CUSTOM_INDEXES as $tableName => $indexes) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);

            foreach ($indexes as $indexName => $columns) {
                if (!$table->hasIndex($indexName)) {
                    $table->addIndex($columns, $indexName);
                }
            }
        }
    }
}
