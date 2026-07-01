<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reverse proxy serving an external site (a GitBook documentation space) under a
 * dedicated subdomain (e.g. doc.dialog.beta.gouv.fr), without changing the URL in
 * the browser.
 *
 * It is enabled only when APP_DOC_PROXY_HOST matches the incoming Host header, so it
 * has no effect on the main application. Internal GitBook links, which are prefixed
 * with the space path, are rewritten so navigation stays on the proxy domain.
 */
final class DocProxySubscriber implements EventSubscriberInterface
{
    /**
     * Response headers that must not be forwarded as-is. Content encoding/length are
     * dropped because the HTTP client transparently decodes the upstream body, and
     * hop-by-hop headers are connection-specific.
     */
    private const STRIPPED_RESPONSE_HEADERS = [
        'content-encoding',
        'content-length',
        'transfer-encoding',
        'connection',
        'keep-alive',
        'set-cookie',
    ];

    /**
     * Content types whose body is text and therefore eligible for link rewriting.
     */
    private const REWRITABLE_CONTENT_TYPES = [
        'text/html',
        'text/x-component',
        'application/json',
        'text/plain',
    ];

    private string $targetOrigin;
    private string $targetPath;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(default::APP_DOC_PROXY_HOST)%')]
        private ?string $docProxyHost,
        #[Autowire('%env(default::APP_DOC_PROXY_TARGET)%')]
        private string $docProxyTarget,
    ) {
        $target = rtrim($this->docProxyTarget, '/');
        $parts = parse_url($target);
        $this->targetOrigin = isset($parts['scheme'], $parts['host'])
            ? \sprintf('%s://%s', $parts['scheme'], $parts['host'])
            : '';
        $this->targetPath = $parts['path'] ?? '';
    }

    public static function getSubscribedEvents(): array
    {
        // High priority so it runs before routing and the security firewall.
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 512],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (empty($this->docProxyHost) || '' === $this->targetOrigin) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getHost() !== $this->docProxyHost) {
            return;
        }

        $upstreamUrl = $this->targetOrigin . $this->targetPath . $this->upstreamPath($request->getRequestUri());

        try {
            $upstreamResponse = $this->httpClient->request($request->getMethod(), $upstreamUrl, [
                'headers' => [
                    'Accept' => $request->headers->get('Accept', '*/*'),
                    'Accept-Language' => $request->headers->get('Accept-Language', ''),
                    'User-Agent' => $request->headers->get('User-Agent', 'dialog-doc-proxy'),
                ],
                'body' => \in_array($request->getMethod(), ['GET', 'HEAD'], true) ? null : $request->getContent(),
                'max_redirects' => 0,
            ]);

            $statusCode = $upstreamResponse->getStatusCode();
            $upstreamHeaders = $upstreamResponse->getHeaders(false);
            $body = $upstreamResponse->getContent(false);
        } catch (HttpClientExceptionInterface) {
            $event->setResponse(new Response('Bad Gateway', Response::HTTP_BAD_GATEWAY));

            return;
        }

        $contentType = $upstreamHeaders['content-type'][0] ?? '';

        if ($this->isRewritable($contentType)) {
            $body = $this->rewriteLinks($body);
        }

        $response = new Response($body, $statusCode);

        foreach ($upstreamHeaders as $name => $values) {
            if (\in_array($name, self::STRIPPED_RESPONSE_HEADERS, true)) {
                continue;
            }
            $value = $values[0] ?? '';
            if ('location' === $name) {
                $value = $this->rewriteLinks($value);
            }
            $response->headers->set($name, $value);
        }

        $event->setResponse($response);
    }

    /**
     * GitBook canonicalises space URLs without a trailing slash, so the space root
     * ("/") must map to the bare space path to avoid a redirect loop.
     */
    private function upstreamPath(string $requestUri): string
    {
        return '/' === $requestUri ? '' : $requestUri;
    }

    private function isRewritable(string $contentType): bool
    {
        foreach (self::REWRITABLE_CONTENT_TYPES as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rewrite upstream links so they point back to the proxy domain. GitBook serves
     * the space under a path prefix (the space path); we turn absolute upstream URLs
     * into root-relative ones and strip that prefix so links resolve at the root of
     * the subdomain.
     */
    private function rewriteLinks(string $body): string
    {
        if ('' === $this->targetPath) {
            return $body;
        }

        // Both the plain ("/") and the JSON-escaped ("\/") slash variants occur in the
        // upstream HTML (inline JSON payloads escape slashes).
        foreach (['/', '\\/'] as $slash) {
            $origin = str_replace('/', $slash, $this->targetOrigin);
            $path = str_replace('/', $slash, $this->targetPath);

            // Absolute upstream URLs become root-relative on the proxy domain.
            $body = str_replace($origin, '', $body);

            $quotedPath = preg_quote($path, '#');
            $quotedSlash = preg_quote($slash, '#');
            // The leading-slash look-behind prevents rewriting the proxy's own host
            // (e.g. "https://doc.dialog.beta.gouv.fr").
            $lookBehind = '(?<!' . preg_quote(substr($slash, -1), '#') . ')';

            // Sub-pages: drop the prefix, the following slash already separates the path.
            $body = preg_replace('#' . $lookBehind . $quotedPath . '(?=' . $quotedSlash . ')#', '', $body) ?? $body;
            // Space root: collapse the prefix to a single slash.
            $body = preg_replace('#' . $lookBehind . $quotedPath . '(?![\w.])#', $slash, $body) ?? $body;
        }

        return $body;
    }
}
