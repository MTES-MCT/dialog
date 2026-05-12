<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\StorageInterface;
use App\Domain\Regulation\RegulationMapImage;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Infrastructure\Adapter\RegulationMapImageMaker;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class RegulationMapImageMakerTest extends TestCase
{
    public function testReturnsNullWhenNoGeometries(): void
    {
        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $locationRepository
            ->expects(self::once())
            ->method('findGeometriesForRegulationOrderRecord')
            ->with('record-uuid')
            ->willReturn([]);

        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('read');
        $storage->expects(self::never())->method('writeContent');

        $maker = new RegulationMapImageMaker(
            $locationRepository,
            $storage,
            new NullLogger(),
            '/projectDir',
            'http://internal',
        );

        $this->assertNull($maker->make('record-uuid'));
    }

    public function testReturnsCachedJpegWithoutInvokingPlaywright(): void
    {
        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $locationRepository
            ->expects(self::once())
            ->method('findGeometriesForRegulationOrderRecord')
            ->with('record-uuid')
            ->willReturn([
                [
                    'geometry' => '{"type":"LineString","coordinates":[[2.35,48.85],[2.36,48.86]]}',
                    'measure_type' => 'noEntry',
                ],
            ]);

        $jpegBytes = "\xFF\xD8\xFF\xE0fake-jpeg-bytes";
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects(self::once())
            ->method('read')
            ->willReturn($jpegBytes);
        // Cache hit must short-circuit before the Playwright render path that would write back.
        $storage->expects(self::never())->method('writeContent');

        $maker = new RegulationMapImageMaker(
            $locationRepository,
            $storage,
            new NullLogger(),
            '/projectDir',
            'http://internal',
        );

        $result = $maker->make('record-uuid');

        $this->assertInstanceOf(RegulationMapImage::class, $result);
        $this->assertSame(base64_encode($jpegBytes), $result->base64Jpeg);
        $this->assertSame(['noEntry'], $result->measureTypes);
    }
}
