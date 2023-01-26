<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Ensure XHR requests (such as from Turbo) update the web Profiler toolbar.
 * https://symfony.com/doc/current/profiler.html#updating-the-web-debug-toolbar-after-ajax-requests
 */
class WebProfilerXHRSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['onKernelResponse', 0],
            ],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->kernel->isDebug()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->headers->has('Turbo-Frame')) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Symfony-Debug-Toolbar-Replace', '1');
    }
}
