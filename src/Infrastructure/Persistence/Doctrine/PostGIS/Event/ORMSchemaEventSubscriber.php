<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\PostGIS\Event;

use App\Infrastructure\Persistence\Doctrine\DBAL\Types\GeoJSONGeometryType;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Types\Type;
use Jsor\Doctrine\PostGIS\Event\ORMSchemaEventSubscriber as BaseORMSchemaEventSubscriber;

// Credit: https://github.com/coopcycle/coopcycle-web/blob/c9ea24efcbeb11d54d106ef6ae641706d90c3c74/src/Doctrine/PostGIS/ORMSchemaEventSubscriber.php
class ORMSchemaEventSubscriber extends BaseORMSchemaEventSubscriber
{
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args): void
    {
        parent::onSchemaColumnDefinition($args);
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
