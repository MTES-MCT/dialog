<?php

declare(strict_types=1);

namespace App\Domain\Geography;

class HouseNumber
{
    private const NUMBER_RE = '/^(?P<number>\d+).*$/i';

    /**
     * Return true if $left is after or the same as $right, false otherwise.
     */
    public static function compare(string $left, string $right): bool
    {
        if (!preg_match(self::NUMBER_RE, $left, $matchLeft)) {
            throw new \RuntimeException(sprintf('left is not a valid house number: %s', $left));
        }

        if (!preg_match(self::NUMBER_RE, $right, $matchRight)) {
            throw new \RuntimeException(sprintf('right is not a valid house number: %s', $right));
        }

        return (int) $matchLeft['number'] <= (int) $matchRight['number'];
    }
}
