<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\PostGIS\Event;

use App\Infrastructure\Persistence\Doctrine\DBAL\Types\GeoJSONGeometryType;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Types\Type;
use Jsor\Doctrine\PostGIS\Event\ORMSchemaEventListener as BaseORMSchemaEventListener;

class ORMSchemaEventListener extends BaseORMSchemaEventListener
{
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args): void
    {
        $this->handleGeoJsonGeometryTypeComments($args);
    }

    private function handleGeoJsonGeometryTypeComments(SchemaColumnDefinitionEventArgs $args): void
    {
        $comment = $args->getColumn()?->getComment();

        if (!$comment) {
            return;
        }

        $geoJSONGeometryType = Type::getType((new GeoJSONGeometryType())->getName());
        $geoJSONComment = $args->getConnection()->getDriver()->getDatabasePlatform()->getDoctrineTypeComment($geoJSONGeometryType);
        if ($comment === $geoJSONComment) {
            $args->getColumn()->setType($geoJSONGeometryType);
        }
    }
}
