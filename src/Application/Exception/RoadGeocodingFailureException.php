<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class RoadGeocodingFailureException extends GeocodingFailureException
{
    public function __construct(
        public readonly string $roadType,
        string $message = '',
        ?int $locationIndex = null,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $locationIndex, $previous);
    }
}
