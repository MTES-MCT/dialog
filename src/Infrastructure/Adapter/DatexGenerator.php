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

        $content = $this->twig->render('api/regulations.xml.twig', [
            'publicationTime' => $this->dateUtils->getNow(),
            'regulationOrders' => $regulationOrders,
        ]);

        $dir = \dirname($this->datexFilePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $tmpFile = $this->datexFilePath . '.tmp';
        file_put_contents($tmpFile, $content);
        rename($tmpFile, $this->datexFilePath);
    }

    public function getDatexFilePath(): string
    {
        return $this->datexFilePath;
    }
}
