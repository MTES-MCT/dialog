<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\IgnReportClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class IgnReportClientTest extends TestCase
{
    private MockObject $ignReportClient;
    private MockObject $logger;
    private MockObject $mockResponse;
    private IgnReportClient $client;

    protected function setUp(): void
    {
        $this->ignReportClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);

        $this->client = new IgnReportClient(
            $this->ignReportClient,
            $this->logger,
            'test', // defaultStatus depuis variable d'environnement
        );
    }

    public function testSubmitReportWithValidData(): void
    {
        $comment = 'Il y a un problème avec cette route';
        $geometry = 'POINT(2.3522 48.8566)';

        $this->mockResponse
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(201);

        $this->ignReportClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/reports',
                $this->callback(function ($options) use ($comment, $geometry) {
                    $this->assertArrayHasKey('json', $options);
                    $json = $options['json'];

                    $this->assertSame(1, $json['community']);
                    $this->assertSame($geometry, $json['geometry']);
                    $this->assertSame($comment, $json['comment']);
                    $this->assertSame('test', $json['status']);
                    $this->assertArrayHasKey('attributes', $json);
                    $this->assertSame(1, $json['attributes']['community']);
                    $this->assertSame('Route', $json['attributes']['theme']);

                    return true;
                }),
            )
            ->willReturn($this->mockResponse);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Report sent to IGN API',
                [
                    'geometry' => $geometry,
                    'comment' => $comment,
                    'statusCode' => 201,
                ],
            );

        $response = $this->client->submitReport($comment, $geometry);

        $this->assertSame($this->mockResponse, $response);
    }

    public function testSubmitReportWithEmptyCommentThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment is required');

        $this->ignReportClient
            ->expects($this->never())
            ->method('request');

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->client->submitReport('', 'POINT(2.3522 48.8566)');
    }

    public function testSubmitReportWithEmptyGeometryThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Geometry is required');

        $this->ignReportClient
            ->expects($this->never())
            ->method('request');

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->client->submitReport('Un commentaire valide', '');
    }

    public function testSubmitReportLogsSuccessWithStatusCode(): void
    {
        $comment = 'Problème de signalisation';
        $geometry = 'POINT(1.4437 43.6047)';

        $this->mockResponse
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->ignReportClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($this->mockResponse);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Report sent to IGN API',
                $this->callback(function ($context) use ($comment, $geometry) {
                    $this->assertArrayHasKey('geometry', $context);
                    $this->assertArrayHasKey('comment', $context);
                    $this->assertArrayHasKey('statusCode', $context);
                    $this->assertSame($geometry, $context['geometry']);
                    $this->assertSame($comment, $context['comment']);
                    $this->assertSame(200, $context['statusCode']);

                    return true;
                }),
            );

        $this->client->submitReport($comment, $geometry);
    }

    public function testSubmitReportWithWhitespaceOnlyCommentThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment is required');

        $this->client->submitReport('   ', 'POINT(2.3522 48.8566)');
    }

    public function testSubmitReportWithWhitespaceOnlyGeometryThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Geometry is required');

        $this->client->submitReport('Un commentaire valide', '   ');
    }

    public function testSubmitReportPayloadStructure(): void
    {
        $comment = 'Test de structure du payload';
        $geometry = 'POINT(0 0)';

        $this->mockResponse
            ->method('getStatusCode')
            ->willReturn(201);

        $capturedPayload = null;

        $this->ignReportClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/reports',
                $this->callback(function ($options) use (&$capturedPayload) {
                    $capturedPayload = $options['json'];

                    return true;
                }),
            )
            ->willReturn($this->mockResponse);

        $this->logger
            ->method('info');

        $this->client->submitReport($comment, $geometry);

        // Vérifier la structure complète du payload
        $this->assertIsArray($capturedPayload);
        $this->assertArrayHasKey('community', $capturedPayload);
        $this->assertArrayHasKey('geometry', $capturedPayload);
        $this->assertArrayHasKey('comment', $capturedPayload);
        $this->assertArrayHasKey('status', $capturedPayload);
        $this->assertArrayHasKey('attributes', $capturedPayload);

        $this->assertIsArray($capturedPayload['attributes']);
        $this->assertArrayHasKey('community', $capturedPayload['attributes']);
        $this->assertArrayHasKey('theme', $capturedPayload['attributes']);
    }

    public function testSubmitReportWithCustomStatus(): void
    {
        $comment = 'Problème à corriger';
        $geometry = 'POINT(2.3522 48.8566)';
        $status = 'submit';

        $this->mockResponse
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(201);

        $this->ignReportClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/reports',
                $this->callback(function ($options) use ($status) {
                    $this->assertArrayHasKey('json', $options);
                    $json = $options['json'];
                    $this->assertSame($status, $json['status']);

                    return true;
                }),
            )
            ->willReturn($this->mockResponse);

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->client->submitReport($comment, $geometry, $status);
    }

    public function testSubmitReportWithDefaultStatus(): void
    {
        $comment = 'Test avec status par défaut';
        $geometry = 'POINT(2.3522 48.8566)';

        $this->mockResponse
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(201);

        $this->ignReportClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/reports',
                $this->callback(function ($options) {
                    $this->assertArrayHasKey('json', $options);
                    $json = $options['json'];
                    $this->assertSame('test', $json['status']);

                    return true;
                }),
            )
            ->willReturn($this->mockResponse);

        $this->logger
            ->expects($this->once())
            ->method('info');

        // Ne pas passer de status, doit utiliser 'test' par défaut
        $this->client->submitReport($comment, $geometry);
    }

    public function testSubmitReportUsesEnvironmentDefaultStatus(): void
    {
        $comment = 'Test avec status depuis variable d\'environnement';
        $geometry = 'POINT(2.3522 48.8566)';

        // Créer un client avec un defaultStatus différent (simule variable d'environnement)
        $clientWithSubmitStatus = new IgnReportClient(
            $this->ignReportClient,
            $this->logger,
            'submit',
        );

        $this->mockResponse
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(201);

        $this->ignReportClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/reports',
                $this->callback(function ($options) {
                    $this->assertArrayHasKey('json', $options);
                    $json = $options['json'];
                    $this->assertSame('submit', $json['status']);

                    return true;
                }),
            )
            ->willReturn($this->mockResponse);

        $this->logger
            ->expects($this->once())
            ->method('info');

        // Ne pas passer de status, doit utiliser 'submit' depuis la variable d'environnement
        $clientWithSubmitStatus->submitReport($comment, $geometry);
    }
}
