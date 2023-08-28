<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

final class EudonetParisLogger
{
    public function __construct(
        private KernelInterface $kernel,
        private Filesystem $fs,
        private string $eudonetParisLogDir,
    ) {
    }

    public function log(string $content, \DateTimeInterface $dateUTC): void
    {
        $env = $this->kernel->getEnvironment();
        $logFileName = sprintf('%s/Import%s.%s.log', $this->eudonetParisLogDir, $dateUTC->format('YmdHis'), $env);
        $this->fs->dumpFile($logFileName, $content);
    }
}
