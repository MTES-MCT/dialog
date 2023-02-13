<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\ContentSecurityPolicy;

use Nelmio\SecurityBundle\ContentSecurityPolicy\NonceGenerator as ParentNonceGenerator;
use Nelmio\SecurityBundle\ContentSecurityPolicy\NonceGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class NonceGenerator implements NonceGeneratorInterface, EventSubscriberInterface
{
    private ?string $requestNonce = null;

    public function __construct(
        private readonly ParentNonceGenerator $parent,
    ) {
    }

    public function generate(): string
    {
        if ($this->requestNonce) {
            return $this->requestNonce;
        } else {
            return $this->parent->generate();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 400],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->getRequest()->headers->has('X-CSP-Nonce')) {
            $this->requestNonce = $event->getRequest()->headers->get('X-CSP-Nonce');
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->requestNonce = null;
    }
}
