<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

final class ValidGeoJsonGeometryConstraint extends Constraint
{
    /**
     * @param string[] $types Types GeoJSON acceptés (ex. 'Polygon'). Vide : tous les types.
     */
    #[HasNamedArguments]
    public function __construct(
        public array $types = [],
        public string $typeMessage = 'geojson.error.invalid_geometry_type',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
