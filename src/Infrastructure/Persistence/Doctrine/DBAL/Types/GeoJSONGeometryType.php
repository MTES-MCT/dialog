<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Jsor\Doctrine\PostGIS\Types\GeometryType;

// Credit: https://github.com/coopcycle/coopcycle-web/blob/c9ea24efcbeb11d54d106ef6ae641706d90c3c74/src/Doctrine/DBAL/Types/GeoJSONType.php
class GeoJSONGeometryType extends GeometryType
{
    public function getName(): string
    {
        return 'geojson_geometry';
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return ['geojson_geometry'];
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $options = $this->getNormalizedPostGISColumnOptions($column);

        return sprintf(
            '%s(%s, %d)',
            'geometry', // would use getName() ('geojson_geometry') by default, but the PostGIS type is still 'geometry'
            $options['geometry_type'],
            $options['srid'],
        );
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        return sprintf('ST_GeomFromGeoJSON(%s)', $sqlExpr);
    }

    public function convertToPHPValueSQL($sqlExpr, $platform): string
    {
        return sprintf('ST_AsGeoJSON(%s)', $sqlExpr);
    }
}
