<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Domain\Regulation\RegulationMapImageMakerInterface;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final class RegulationMapImageMaker implements RegulationMapImageMakerInterface
{
    private const RENDER_WIDTH = 600;
    private const RENDER_HEIGHT = 420;
    private const RENDER_TIMEOUT_SECONDS = 30;
    private const PAGE_TIMEOUT_MS = 15000;
    private const JPEG_SIGNATURE = "\xFF\xD8\xFF";

    public function __construct(
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
        private readonly string $internalAppUrl,
    ) {
    }

    public function makeBase64Jpeg(string $regulationOrderRecordUuid): ?string
    {
        $rows = $this->locationRepository->findGeometriesForRegulationOrderRecord($regulationOrderRecordUuid);

        if ($rows === []) {
            return null;
        }

        $bounds = $this->computeBounds($rows);

        if ($bounds === null) {
            return null;
        }

        $cachePath = $this->getCachePath($regulationOrderRecordUuid, $rows);

        if (is_file($cachePath)) {
            $cached = @file_get_contents($cachePath);

            if ($cached !== false) {
                return base64_encode($cached);
            }
        }

        $jpeg = $this->renderViaPlaywright($regulationOrderRecordUuid, $bounds);

        if ($jpeg === null) {
            return null;
        }

        $this->writeCache($cachePath, $jpeg);

        return base64_encode($jpeg);
    }

    /**
     * @param list<array{geometry: string, measure_type: string}> $rows
     *
     * @return array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}|null
     */
    private function computeBounds(array $rows): ?array
    {
        $minLon = \INF;
        $minLat = \INF;
        $maxLon = -\INF;
        $maxLat = -\INF;
        $found = false;

        foreach ($rows as $row) {
            $geometry = json_decode($row['geometry'], true);

            if (!\is_array($geometry)) {
                continue;
            }

            $this->walkPositions($geometry, function (float $lon, float $lat) use (&$minLon, &$minLat, &$maxLon, &$maxLat, &$found): void {
                if ($lon < $minLon) {
                    $minLon = $lon;
                }

                if ($lat < $minLat) {
                    $minLat = $lat;
                }

                if ($lon > $maxLon) {
                    $maxLon = $lon;
                }

                if ($lat > $maxLat) {
                    $maxLat = $lat;
                }

                $found = true;
            });
        }

        if (!$found) {
            return null;
        }

        // Avoid a degenerate (zero-area) bbox when all points are identical: pad by ~50m.
        $minSpanDeg = 0.0005;

        if ($maxLon - $minLon < $minSpanDeg) {
            $cx = ($maxLon + $minLon) / 2;
            $minLon = $cx - $minSpanDeg / 2;
            $maxLon = $cx + $minSpanDeg / 2;
        }

        if ($maxLat - $minLat < $minSpanDeg) {
            $cy = ($maxLat + $minLat) / 2;
            $minLat = $cy - $minSpanDeg / 2;
            $maxLat = $cy + $minSpanDeg / 2;
        }

        return [[$minLon, $minLat], [$maxLon, $maxLat]];
    }

    private function walkPositions(array $geometry, callable $callback): void
    {
        $type = $geometry['type'] ?? null;

        if ($type === 'GeometryCollection') {
            foreach ($geometry['geometries'] ?? [] as $sub) {
                if (\is_array($sub)) {
                    $this->walkPositions($sub, $callback);
                }
            }

            return;
        }

        $coordinates = $geometry['coordinates'] ?? null;

        if (\is_array($coordinates)) {
            $this->walkCoordinatesArray($coordinates, $callback);
        }
    }

    private function walkCoordinatesArray(array $coords, callable $callback): void
    {
        if (isset($coords[0]) && is_numeric($coords[0]) && isset($coords[1]) && is_numeric($coords[1])) {
            $callback((float) $coords[0], (float) $coords[1]);

            return;
        }

        foreach ($coords as $sub) {
            if (\is_array($sub)) {
                $this->walkCoordinatesArray($sub, $callback);
            }
        }
    }

    /**
     * @param array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}} $bounds
     */
    private function renderViaPlaywright(string $uuid, array $bounds): ?string
    {
        // Pass bounds in the URL so the controller can pre-compute the initial viewport,
        // avoiding a wasted "France @ zoom 5" tile load before fitBounds re-tiles to the actual area.
        $boundsParam = \sprintf('%F,%F,%F,%F', $bounds[0][0], $bounds[0][1], $bounds[1][0], $bounds[1][1]);
        $url = \sprintf('%s/_internal/regulation-map/%s.html?bounds=%s', rtrim($this->internalAppUrl, '/'), $uuid, $boundsParam);
        $payload = json_encode([
            'url' => $url,
            'bounds' => $bounds,
            'width' => self::RENDER_WIDTH,
            'height' => self::RENDER_HEIGHT,
            'timeoutMs' => self::PAGE_TIMEOUT_MS,
        ]);

        if ($payload === false) {
            return null;
        }

        $process = new Process(['node', $this->projectDir . '/scripts/render-regulation-map.cjs']);
        $process->setInput($payload);
        $process->setTimeout(self::RENDER_TIMEOUT_SECONDS);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            $this->logger->warning('Regulation map render timed out.', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$process->isSuccessful()) {
            $this->logger->warning('Regulation map render exited with non-zero status.', [
                'uuid' => $uuid,
                'exit_code' => $process->getExitCode(),
                'stderr' => $process->getErrorOutput(),
            ]);

            return null;
        }

        $jpeg = $process->getOutput();

        if ($jpeg === '' || !str_starts_with($jpeg, self::JPEG_SIGNATURE)) {
            $this->logger->warning('Regulation map render did not return a JPEG.', [
                'uuid' => $uuid,
                'stderr' => $process->getErrorOutput(),
            ]);

            return null;
        }

        $stderr = $process->getErrorOutput();

        if ($stderr !== '') {
            $this->logger->info('Regulation map render timings.', [
                'uuid' => $uuid,
                'stderr' => $stderr,
            ]);
        }

        return $jpeg;
    }

    /**
     * @param list<array{geometry: string, measure_type: string}> $rows
     */
    private function getCachePath(string $uuid, array $rows): string
    {
        $key = sha1(json_encode([$uuid, $rows, self::RENDER_WIDTH, self::RENDER_HEIGHT]));

        return $this->projectDir . '/var/cache/regulation-maps/' . $key . '.png';
    }

    private function writeCache(string $path, string $png): void
    {
        $dir = \dirname($path);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents($path, $png);
    }
}
