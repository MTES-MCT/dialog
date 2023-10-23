<?php

declare(strict_types=1);

namespace App\Tests\Mock\EudonetParis;

use PHPUnit\Framework\Assert;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class EudonetParisMockHttpClient extends MockHttpClient
{
    private string $baseUri = 'https://testserver';
    private array $requests;

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);
        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        $this->requests[] = ['url' => $url, 'options' => $options];

        if ($method === 'POST' && $url === $this->baseUri . '/EudoAPI/Authenticate/Token') {
            $body = json_decode($this->getMockJSON('auth'), true);
            $body['ResultData']['ExpirationDate'] = (new \DateTimeImmutable('now'))->add(new \DateInterval('P1D'))->format('Y/m/d H:i:s');

            return new MockResponse(json_encode($body), ['http_code' => 200]);
        }

        if ($method === 'POST' && $url === $this->baseUri . '/EudoAPI/Search/1100') {
            return new MockResponse($this->getMockJSON('1100'), ['http_code' => 200]);
        }

        if ($method === 'POST' && $url === $this->baseUri . '/EudoAPI/Search/1200') {
            return new MockResponse($this->getMockJSON('1200'), ['http_code' => 200]);
        }

        if ($method === 'POST' && $url === $this->baseUri . '/EudoAPI/Search/2700') {
            return new MockResponse($this->getMockJSON('2700'), ['http_code' => 200]);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method $url");
    }

    private function getMockJSON(string $name): string
    {
        return file_get_contents(__DIR__ . "/eudonet_paris.$name.mock.json");
    }

    public function assertExpectedRequestsMade(): void
    {
        Assert::assertCount(5, $this->requests);

        // Auth
        Assert::assertSame($this->baseUri . '/EudoAPI/Authenticate/Token', $this->requests[0]['url']);
        Assert::assertSame(
            '{"SubscriberLogin": "testSubcriberLogin", "SubscriberPassword": "testSubcriberPassword", "BaseName": "TEST_BASE_NAME", "UserLogin": "testUserLogin", "UserPassword": "testPassword", "UserLang": "lang_00", "ProductName": "api"}',
            $this->requests[0]['options']['body'],
        );

        // Regulation order
        Assert::assertSame($this->baseUri . '/EudoAPI/Search/1100', $this->requests[1]['url']);
        Assert::assertEquals(
            [
                'WhereCustoms' => [
                    [
                        'Criteria' => [
                            'Field' => 1108,
                            'Operator' => 0,
                            'Value' => 8,
                        ],
                    ],
                    [
                        'Criteria' => [
                            'Field' => 1110,
                            'Operator' => 3,
                            'Value' => '2023/06/09 02:00:00',
                        ],
                        'InterOperator' => 1,
                    ],
                ],
            ],
            json_decode($this->requests[1]['options']['body'], true)['WhereCustom'],
        );

        // Measures
        Assert::assertSame($this->baseUri . '/EudoAPI/Search/1200', $this->requests[2]['url']);
        Assert::assertEquals(
            [
                'WhereCustoms' => [
                    [
                        'Criteria' => [
                            'Field' => 1100,
                            'Operator' => 0,
                            'Value' => 43497,
                        ],
                    ],
                    [
                        'Criteria' => [
                            'Field' => 1202,
                            'Operator' => 0,
                            'Value' => '103',
                        ],
                        'InterOperator' => 1,
                    ],
                ],
            ],
            json_decode($this->requests[2]['options']['body'], true)['WhereCustom'],
        );

        // Locations of measure 1
        Assert::assertSame($this->baseUri . '/EudoAPI/Search/2700', $this->requests[3]['url']);
        Assert::assertEquals(
            [
                'Criteria' => [
                    'Field' => 1200,
                    'Operator' => 0,
                    'Value' => 363226,
                ],
            ],
            json_decode($this->requests[3]['options']['body'], true)['WhereCustom'],
        );

        // Locations of measure 2
        Assert::assertSame($this->baseUri . '/EudoAPI/Search/2700', $this->requests[4]['url']);
        Assert::assertEquals(
            [
                'Criteria' => [
                    'Field' => 1200,
                    'Operator' => 0,
                    'Value' => 363227,
                ],
            ],
            json_decode($this->requests[4]['options']['body'], true)['WhereCustom'],
        );
    }
}
