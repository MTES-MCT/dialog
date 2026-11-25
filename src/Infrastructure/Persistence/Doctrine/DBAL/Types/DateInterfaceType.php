<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateType;

/**
 * DBAL 4 only accepts \DateTime for this type: also accept \DateTimeImmutable.
 */
final class DateInterfaceType extends DateType
{
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value instanceof \DateTimeImmutable) {
            $value = \DateTime::createFromImmutable($value);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
