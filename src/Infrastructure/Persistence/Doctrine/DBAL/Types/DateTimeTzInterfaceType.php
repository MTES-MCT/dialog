<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeTzType;

/**
 * DBAL 4 only accepts \DateTime for this type: also accept \DateTimeImmutable.
 */
final class DateTimeTzInterfaceType extends DateTimeTzType
{
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof \DateTimeImmutable) {
            $value = \DateTime::createFromImmutable($value);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
