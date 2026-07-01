<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\EventSubscriber;

use App\Infrastructure\Symfony\EventSubscriber\DocProxySubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class DocProxySubscriberTest extends TestCase
{
    private const HOST = 'doc.dialog.beta.gouv.fr';
    private const TARGET = 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr';

    public function testSubscribedEvents(): void
    {
        $this->assertEquals([
            'kernel.request' => [
                ['onKernelRequest', 512],
            ],
        ], DocProxySubscriber::getSubscribedEvents());
    }

    public function testIgnoredWhenDisabled(): void
    {
        $client = new MockHttpClient(function (): MockResponse {
            $this->fail('The HTTP client must not be called when the proxy is disabled.');
        });
        $subscriber = new DocProxySubscriber($client, '', self::TARGET);

        $event = $this->createEvent(Request::create('https://' . self::HOST . '/'));
        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoredWhenHostDoesNotMatch(): void
    {
        $client = new MockHttpClient(function (): MockResponse {
            $this->fail('The HTTP client must not be called for a non-matching host.');
        });
        $subscriber = new DocProxySubscriber($client, self::HOST, self::TARGET);

        $event = $this->createEvent(Request::create('https://dialog.beta.gouv.fr/'));
        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testRootRequestMapsToBareSpacePath(): void
    {
        $requestedUrl = null;
        $client = new MockHttpClient(function (string $method, string $url) use (&$requestedUrl): MockResponse {
            $requestedUrl = $url;

            return new MockResponse('ok', ['response_headers' => ['content-type' => 'text/html']]);
        });
        $subscriber = new DocProxySubscriber($client, self::HOST, self::TARGET);

        $event = $this->createEvent(Request::create('https://' . self::HOST . '/'));
        $subscriber->onKernelRequest($event);

        // The space root must not carry a trailing slash to avoid a redirect loop.
        $this->assertSame(self::TARGET, $requestedUrl);
    }

    public function testSubPageIsForwardedWithItsPath(): void
    {
        $requestedUrl = null;
        $client = new MockHttpClient(function (string $method, string $url) use (&$requestedUrl): MockResponse {
            $requestedUrl = $url;

            return new MockResponse('ok', ['response_headers' => ['content-type' => 'text/html']]);
        });
        $subscriber = new DocProxySubscriber($client, self::HOST, self::TARGET);

        $event = $this->createEvent(Request::create('https://' . self::HOST . '/demarrer/ajouter-un-logo'));
        $subscriber->onKernelRequest($event);

        $this->assertSame(self::TARGET . '/demarrer/ajouter-un-logo', $requestedUrl);
    }

    public function testInternalLinksAreRewritten(): void
    {
        $body = implode("\n", [
            '<a href="/doc.dialog.beta.gouv.fr/foo">sub</a>',
            '<a href="/doc.dialog.beta.gouv.fr">root</a>',
            '<a href="https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr/bar">abs</a>',
            '<link rel="canonical" href="https://doc.dialog.beta.gouv.fr"/>',
        ]);
        $client = new MockHttpClient(
            new MockResponse($body, ['response_headers' => ['content-type' => 'text/html; charset=utf-8']]),
        );
        $subscriber = new DocProxySubscriber($client, self::HOST, self::TARGET);

        $event = $this->createEvent(Request::create('https://' . self::HOST . '/'));
        $subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $content = $response->getContent();

        $this->assertStringContainsString('<a href="/foo">sub</a>', $content);
        $this->assertStringContainsString('<a href="/">root</a>', $content);
        $this->assertStringContainsString('<a href="/bar">abs</a>', $content);
        // The proxy's own host must be preserved.
        $this->assertStringContainsString('href="https://doc.dialog.beta.gouv.fr"', $content);
        $this->assertStringNotContainsString('/doc.dialog.beta.gouv.fr/', $content);
    }

    public function testBinaryResponsesArePassedThroughUnchanged(): void
    {
        $png = "\x89PNG\r\n\x1a\n/doc.dialog.beta.gouv.fr/not-a-link";
        $client = new MockHttpClient(
            new MockResponse($png, ['response_headers' => ['content-type' => 'image/png']]),
        );
        $subscriber = new DocProxySubscriber($client, self::HOST, self::TARGET);

        $event = $this->createEvent(Request::create('https://' . self::HOST . '/~gitbook/icon'));
        $subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($png, $response->getContent());
        $this->assertSame('image/png', $response->headers->get('content-type'));
    }

    public function testBadGatewayOnTransportError(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['error' => 'connection refused']));
        $subscriber = new DocProxySubscriber($client, self::HOST, self::TARGET);

        $event = $this->createEvent(Request::create('https://' . self::HOST . '/'));
        $subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_BAD_GATEWAY, $response->getStatusCode());
    }

    private function createEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
