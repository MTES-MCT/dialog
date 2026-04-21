<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\DatexGeneratorInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use League\Flysystem\FilesystemOperator;

final class DatexGenerator implements DatexGeneratorInterface
{
    private const DATEX_PATH = 'datex/regulations.xml';

    public function __construct(
        private \Twig\Environment $twig,
        private DateUtilsInterface $dateUtils,
        private QueryBusInterface $queryBus,
        private FilesystemOperator $storage,
    ) {
    }

    public function generate(): void
    {
        $regulationOrders = $this->queryBus->handle(
            new GetRegulationOrdersToDatexFormatQuery(),
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'datex');
        $handle = fopen($tmpFile, 'w');

        ob_start(function (string $buffer) use ($handle): string {
            fwrite($handle, $buffer);

            return '';
        }, 262144);

        $this->twig->display('api/regulations.xml.twig', [
            'publicationTime' => $this->dateUtils->getNow(),
            'regulationOrders' => $regulationOrders,
        ]);

        ob_end_flush();
        fclose($handle);

        $readStream = fopen($tmpFile, 'r');
        $this->storage->writeStream(self::DATEX_PATH, $readStream);

        if (\is_resource($readStream)) {
            fclose($readStream);
        }

        unlink($tmpFile);
    }

    public function getCachedDatex(): string
    {
        if (!$this->storage->fileExists(self::DATEX_PATH)) {
            $this->generate();
        }

        return $this->storage->read(self::DATEX_PATH);
    }
}
