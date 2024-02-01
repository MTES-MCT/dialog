<?php

declare(strict_types=1);

namespace App\Infrastructure\BacIdf;

final class BacIdfExtractor
{
    public function __construct(
        private readonly string $bacIdfDecreesFile,
    ) {
    }

    public function iterExtract(array $ignoreIDs): \Iterator
    {
        $decreesJsonRaw = file_get_contents($this->bacIdfDecreesFile);
        $decreesJson = json_decode($decreesJsonRaw, associative: true);

        foreach ($decreesJson as $row) {
            if (\in_array($row['ARR_REF'], $ignoreIDs)) {
                continue;
            }

            yield $row;
        }
    }
}
