<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\DatexGeneratorInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class DatexGenerator implements DatexGeneratorInterface
{
    private string $datexFilePath;

    public function __construct(
        private \Twig\Environment $twig,
        private DateUtilsInterface $dateUtils,
        private QueryBusInterface $queryBus,
        private Filesystem $filesystem,
        string $projectDir,
    ) {
        $this->datexFilePath = Path::join($projectDir, '/var/datex/regulations.xml');
    }

    public function generate(): void
    {
        $regulationOrders = $this->queryBus->handle(
            new GetRegulationOrdersToDatexFormatQuery(),
        );

        $dir = Path::getDirectory($this->datexFilePath);

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir, 0o755);
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
        $this->filesystem->rename($tmpFile, $this->datexFilePath, overwrite: true);
    }

    public function getCachedDatex(): string
    {
        if (!$this->filesystem->exists($this->datexFilePath)) {
            $this->generate();
        }

        return $this->filesystem->readFile($this->datexFilePath);
    }
}
