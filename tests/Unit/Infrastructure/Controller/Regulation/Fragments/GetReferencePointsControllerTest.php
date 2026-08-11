<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller\Regulation\Fragments;

use App\Application\Exception\GeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use App\Infrastructure\Controller\Regulation\Fragments\GetReferencePointsController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class GetReferencePointsControllerTest extends TestCase
{
    private RoadGeocoderInterface&MockObject $roadGeocoder;
    private LoggerInterface&MockObject $logger;
    private GetReferencePointsController $controller;

    protected function setUp(): void
    {
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new GetReferencePointsController(
            $this->roadGeocoder,
            $this->logger,
        );
    }

    public function testMissingParameters(): void
    {
        $this->roadGeocoder->expects(self::never())->method('computeReferencePoints');

        $response = ($this->controller)(administrator: '', roadNumber: '');

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testSuccess(): void
    {
        $geometry = '{"type":"FeatureCollection","features":[]}';

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeReferencePoints')
            ->with('Ardèche', 'D906')
            ->willReturn($geometry);

        $response = ($this->controller)(administrator: 'Ardèche', roadNumber: 'D906');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($geometry, $response->getContent());
    }

    public function testGeocodingFailureReturnsNoContent(): void
    {
        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeReferencePoints')
            ->willThrowException(new GeocodingFailureException('boom'));

        $response = ($this->controller)(administrator: 'Ardèche', roadNumber: 'D906');

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
