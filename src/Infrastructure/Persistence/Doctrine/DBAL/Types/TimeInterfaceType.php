<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TimeType;

/**
 * DBAL 4 only accepts \DateTime for this type: also accept \DateTimeImmutable.
 */
final class TimeInterfaceType extends TimeType
{
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof \DateTimeImmutable) {
            $value = \DateTime::createFromImmutable($value);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
