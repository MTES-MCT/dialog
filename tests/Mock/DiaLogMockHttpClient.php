<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

// During tests, we want to request the test data, but there is no real test server available via HTTP
// So this replacement client calls the HTTP kernel directly.
final class DiaLogMockHttpClient extends MockHttpClient
{
    public function __construct(private HttpKernelInterface $httpKernel)
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);
        parent::__construct($callback, 'http://testserver');
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        $request = Request::create($url, $method);
        $response = $this->httpKernel->handle($request);

        return new MockResponse($response->getContent());
    }
}
