<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller\Api;

use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetNearbyStreetsQuery;
use App\Infrastructure\Controller\Api\GetNearbyStreetsController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class GetNearbyStreetsControllerTest extends TestCase
{
    private QueryBusInterface&MockObject $queryBus;
    private LoggerInterface&MockObject $logger;
    private GetNearbyStreetsController $controller;

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new GetNearbyStreetsController(
            $this->queryBus,
            $this->logger,
        );
    }

    public function testNearbyStreets(): void
    {
        $expectedStreets = [
            ['roadName' => 'Rue de Rivoli', 'distance' => 12.3],
            ['roadName' => 'Rue Saint-Honoré', 'distance' => 45.7],
        ];

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo(new GetNearbyStreetsQuery(
                geometry: '{"type":"Point","coordinates":[2.35,48.85]}',
                radius: 100,
                limit: 10,
            )))
            ->willReturn($expectedStreets);

        $request = new Request(content: json_encode([
            'geometry' => ['type' => 'Point', 'coordinates' => [2.35, 48.85]],
        ]));

        $response = ($this->controller)($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(json_encode($expectedStreets), $response->getContent());
    }

    public function testNearbyStreetsWithCustomRadiusAndLimit(): void
    {
        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo(new GetNearbyStreetsQuery(
                geometry: '{"type":"Point","coordinates":[2.35,48.85]}',
                radius: 200,
                limit: 5,
            )))
            ->willReturn([['roadName' => 'Rue de Rivoli', 'distance' => 12.3]]);

        $request = new Request(content: json_encode([
            'geometry' => ['type' => 'Point', 'coordinates' => [2.35, 48.85]],
            'radius' => 200,
            'limit' => 5,
        ]));

        $response = ($this->controller)($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRadiusClampedToMax500(): void
    {
        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo(new GetNearbyStreetsQuery(
                geometry: '{"type":"Point","coordinates":[2.35,48.85]}',
                radius: 500,
                limit: 10,
            )))
            ->willReturn([]);

        $request = new Request(content: json_encode([
            'geometry' => ['type' => 'Point', 'coordinates' => [2.35, 48.85]],
            'radius' => 9999,
        ]));

        $response = ($this->controller)($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testLimitClampedToMax50(): void
    {
        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo(new GetNearbyStreetsQuery(
                geometry: '{"type":"Point","coordinates":[2.35,48.85]}',
                radius: 100,
                limit: 50,
            )))
            ->willReturn([]);

        $request = new Request(content: json_encode([
            'geometry' => ['type' => 'Point', 'coordinates' => [2.35, 48.85]],
            'limit' => 9999,
        ]));

        $response = ($this->controller)($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testInvalidJsonBody(): void
    {
        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $request = new Request(content: '{invalid json');

        $response = ($this->controller)($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid JSON body', $data['error']);
    }

    public function testMissingGeometry(): void
    {
        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $request = new Request(content: json_encode(['radius' => 100]));

        $response = ($this->controller)($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('GeoJSON geometry required', $data['error']);
    }

    public function testInvalidGeometryNotAnObject(): void
    {
        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $request = new Request(content: json_encode(['geometry' => 'not-an-object']));

        $response = ($this->controller)($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid GeoJSON geometry', $data['error']);
    }

    public function testInvalidGeometryMissingType(): void
    {
        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $request = new Request(content: json_encode([
            'geometry' => ['coordinates' => [2.35, 48.85]],
        ]));

        $response = ($this->controller)($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid GeoJSON geometry', $data['error']);
    }

    public function testEmptyResult(): void
    {
        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->willReturn([]);

        $request = new Request(content: json_encode([
            'geometry' => ['type' => 'Point', 'coordinates' => [0, 0]],
        ]));

        $response = ($this->controller)($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('[]', $response->getContent());
    }

    public function testGeocodingFailure(): void
    {
        $exception = new GeocodingFailureException('Database error');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Nearby streets query failed', [
                'exception' => 'Database error',
            ]);

        $request = new Request(content: json_encode([
            'geometry' => ['type' => 'Point', 'coordinates' => [2.35, 48.85]],
        ]));

        $response = ($this->controller)($request);

        $this->assertSame(500, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Nearby streets query failed', $data['error']);
    }
}
