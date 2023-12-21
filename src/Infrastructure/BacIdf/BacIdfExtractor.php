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

            if (!$this->shouldProcess($row)) {
                continue;
            }

            yield $row;
        }
    }

    private function shouldProcess(array $row): bool
    {
        if (empty($row['REG_TYPE']) || $row['REG_TYPE'] !== 'CIRCULATION') {
            return false;
        }

        foreach ($row['REG_CIRCULATION'] as $regCirculation) {
            if (!\array_key_exists('REG_VOIES', $regCirculation['CIRC_REG'])) {
                // Full-city regulation, skip
                return false;
            }

            foreach ($regCirculation['CIRC_REG']['REG_VOIES'] as $regVoie) {
                if (empty($regVoie['VOIE_GEOJSON'])) {
                    // Unclean case of full-city regulation, skip
                    return false;
                }
            }
        }

        return true;
    }
}
