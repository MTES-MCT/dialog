<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EudonetParis;

use App\Application\DateUtilsInterface;
use App\Infrastructure\EudonetParis\EudonetParisClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class EudonetParisClientTest extends TestCase
{
    private $dateUtils;
    private $logger;

    protected function setUp(): void
    {
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testSearch(): void
    {
        $now = new \DateTimeImmutable('2023-08-30 14:00:00 Europe/Paris');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $expectedRequests = [
            function ($method, $url, $options) {
                $this->assertSame('POST', $method);
                $this->assertSame('https://testserver/EudoAPI/Authenticate/Token', $url);
                $this->assertContains('Content-Type: application/json', $options['headers']);
                $this->assertSame('credentials...', $options['body']);

                return new MockResponse(json_encode([
                    'ResultData' => [
                        'Token' => 'fake_token',
                        'ExpirationDate' => '2023/08/30 18:00:00',
                    ],
                ]), ['http_code' => 200]);
            },

            function ($method, $url, $options) {
                $this->assertSame('POST', $method);
                $this->assertSame('https://testserver/EudoAPI/Search/1100', $url);
                $this->assertContains('Content-Type: application/json', $options['headers']);
                $this->assertContains('X-Auth: fake_token', $options['headers']);
                $this->assertEquals(json_encode([
                    'ShowMetadata' => true,
                    'RowsPerPage' => 50,
                    'NumPage' => 1,
                    'ListCols' => [1101, 1102],
                    'WhereCustom' => [],
                ]), $options['body']);

                return new MockResponse(json_encode([
                    'ResultData' => [
                        'Rows' => [
                            [
                                'FileId' => 'arrete1',
                                'Fields' => [
                                    ['DescId' => 1101, 'Value' => 'arrete1'],
                                    ['DescId' => 1102, 'Value' => 'Description 1'],
                                ],
                            ],
                        ],
                    ],
                    'ResultMetaData' => [
                        'TotalPages' => 2,
                    ],
                ]), ['http_code' => 200]);
            },

            function ($method, $url, $options) {
                $this->assertSame('POST', $method);
                $this->assertSame('https://testserver/EudoAPI/Search/1100', $url);
                $this->assertContains('Content-Type: application/json', $options['headers']);
                $this->assertContains('X-Auth: fake_token', $options['headers']);
                $this->assertEquals(json_encode([
                    'ShowMetadata' => true,
                    'RowsPerPage' => 50,
                    'NumPage' => 2,
                    'ListCols' => [1101, 1102],
                    'WhereCustom' => [],
                ]), $options['body']);

                return new MockResponse(json_encode([
                    'ResultData' => [
                        'Rows' => [
                            [
                                'FileId' => 'arrete2',
                                'Fields' => [
                                    ['DescId' => 1101, 'Value' => 'arrete2'],
                                    ['DescId' => 1102, 'Value' => 'Description 2'],
                                ],
                            ],
                        ],
                    ],
                    'ResultMetaData' => [
                        'TotalPages' => 2,
                    ],
                ]), ['http_code' => 200]);
            },
        ];

        $http = new MockHttpClient($expectedRequests, 'https://testserver');

        $logMatcher = self::exactly(4);
        $this->logger
            ->expects($logMatcher)
            ->method('debug')
            ->willReturnCallback(fn ($message, $context) => match ($logMatcher->getInvocationCount()) {
                1 => $this->assertEquals($message, 'request'),
                2 => $this->assertEquals($message, 'response'),
                3 => $this->assertEquals($message, 'request'),
                4 => $this->assertEquals($message, 'response'),
            });

        $client = new EudonetParisClient($http, 'credentials...', $this->dateUtils, $this->logger);

        // Simulate getting fields 1101 and 1102 of all regulation orders
        $rows = $client->search(1100, [1101, 1102], []);

        $this->assertEquals(
            [
                [
                    'fileId' => 'arrete1',
                    'fields' => [
                        1101 => 'arrete1',
                        1102 => 'Description 1',
                    ],
                ],
                [
                    'fileId' => 'arrete2',
                    'fields' => [
                        1101 => 'arrete2',
                        1102 => 'Description 2',
                    ],
                ],
            ],
            $rows,
        );
    }

    public function testNonJsonResponseLogsOk()
    {
        $expectedRequests = [
            function ($method, $url, $options) {
                $this->assertSame('https://testserver/EudoAPI/Authenticate/Token', $url);

                return new MockResponse(
                    json_encode(['ResultData' => ['Token' => 'fake_token', 'ExpirationDate' => '2023/08/30 18:00:00']]),
                    ['http_code' => 200],
                );
            },

            function ($method, $url, $options) {
                $this->assertSame('POST', $method);
                $this->assertSame('https://testserver/EudoAPI/Search/1100', $url);

                return new MockResponse('<h1>Hello, world</h1>', ['http_code' => 500]);
            },
        ];

        $http = new MockHttpClient($expectedRequests, 'https://testserver');

        $logMatcher = self::exactly(2);
        $this->logger
            ->expects($logMatcher)
            ->method('debug')
            ->willReturnCallback(fn ($message, $context) => match ($logMatcher->getInvocationCount()) {
                1 => $this->assertEquals($message, 'request'),
                2 => $this->assertEquals($message, 'response') ?: $this->assertSame('Syntax error', $context['json_decode_error']),
            });

        $client = new EudonetParisClient($http, 'credentials...', $this->dateUtils, $this->logger);

        // We'll get an exception from the response processing code due to 500 error, but logging the response went fine.
        $this->expectException(\RuntimeException::class);
        $client->search(1100, [1101, 1102], []);
    }
}
