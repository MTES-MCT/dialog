<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class OrganizationCannotInterveneOnGeometryException extends \Exception
{
    public function __construct(
        string $message = '',
        private ?int $locationIndex = null,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getLocationIndex(): ?int
    {
        return $this->locationIndex;
    }

    public function setLocationIndex(int $index): void
    {
        $this->locationIndex = $index;
    }
}
