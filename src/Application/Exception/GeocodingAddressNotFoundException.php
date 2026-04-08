<?php

declare(strict_types=1);

namespace App\Application\Exception;

class GeocodingAddressNotFoundException extends \Exception
{
    public function __construct(
        string $message = '',
    ) {
        parent::__construct($message);
    }
}
