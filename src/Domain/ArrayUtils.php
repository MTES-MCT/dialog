<?php

declare(strict_types=1);

namespace App\Domain;

final class ArrayUtils
{
    public function groupBy(callable $keyfunc, array $array): array
    {
        $groups = [];

        if ($array) {
            foreach ($array as $item) {
                $key = $keyfunc($item);
                $groups[$key][] = $item;
            }
        }

        return $groups;
    }
}
