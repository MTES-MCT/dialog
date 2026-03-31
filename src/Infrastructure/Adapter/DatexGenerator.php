<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\DatexGeneratorInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;

final class DatexGenerator implements DatexGeneratorInterface
{
    private string $datexFilePath;

    public function __construct(
        private \Twig\Environment $twig,
        private DateUtilsInterface $dateUtils,
        private QueryBusInterface $queryBus,
        string $projectDir,
    ) {
        $this->datexFilePath = $projectDir . '/var/datex/regulations.xml';
    }

    public function generate(): void
    {
        $regulationOrders = $this->queryBus->handle(
            new GetRegulationOrdersToDatexFormatQuery(),
        );

        $dir = \dirname($this->datexFilePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $tmpFile = $this->datexFilePath . '.tmp';
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
        rename($tmpFile, $this->datexFilePath);
    }

    public function getCachedDatex(): string
    {
        if (!file_exists($this->datexFilePath)) {
            $this->generate();
        }

        return file_get_contents($this->datexFilePath);
    }
}
