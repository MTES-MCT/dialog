<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class GeocodingFailureWithLocationIndexException extends \Exception
{
    private int $locationIndex;

    public function __construct(
        int $locationIndex,
        string $message,
        \Exception $previous = null,
    ) {
        $this->locationIndex = $locationIndex;

        parent::__construct($message, 0, $previous);
    }

    public function getLocationIndex(): string
    {
        return (string) $this->locationIndex;
    }
}
