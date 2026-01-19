<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\CsvExporterInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class CsvExporter implements CsvExporterInterface
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function export(array $data): string
    {
        $csv = $this->serializer->serialize($data, CsvEncoder::FORMAT, [
            CsvEncoder::DELIMITER_KEY => ';',
            CsvEncoder::KEY_SEPARATOR_KEY => ',',
        ]);

        return mb_convert_encoding($csv, 'ISO-8859-1', 'UTF-8');
    }
}
