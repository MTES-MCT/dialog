<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\EventSubscriber\RateLimiterSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimiterSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $this->assertEquals([
            'kernel.request' => [
                ['onKernelRequest', 0],
            ],
        ], RateLimiterSubscriber::getSubscribedEvents());
    }

    public function testDebug(): void
    {
        $rateLimiter = $this->createMock(RateLimiterFactory::class);
        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $event = new RequestEvent($kernel, $request, 1);

        $kernel
            ->expects(self::once())
            ->method('isDebug')
            ->willReturn(true);

        $rateLimiter
            ->expects(self::never())
            ->method('create');

        $subscriber = new RateLimiterSubscriber($kernel, $rateLimiter);
        $subscriber->onKernelRequest($event);
    }

    public function testProductionNotLimited(): void
    {
        $rateLimit = $this->createMock(RateLimit::class);
        $rateLimit
            ->expects(self::once())
            ->method('isAccepted')
            ->willReturn(true);

        $rateLimiter = $this->createMock(RateLimiterFactory::class);
        $kernel = $this->createMock(KernelInterface::class);

        $limiter = $this->createMock(LimiterInterface::class);
        $limiter
            ->expects(self::once())
            ->method('consume')
            ->with(1)
            ->willReturn($rateLimit);

        $request = $this->createMock(Request::class);
        $request
            ->expects(self::once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1');

        $kernel
            ->expects(self::once())
            ->method('isDebug')
            ->willReturn(false);

        $rateLimiter
            ->expects(self::once())
            ->method('create')
            ->with('127.0.0.1')
            ->willReturn($limiter);

        $event = new RequestEvent($kernel, $request, 1);
        $subscriber = new RateLimiterSubscriber($kernel, $rateLimiter);
        $subscriber->onKernelRequest($event);
    }

    public function testProductionLimited(): void
    {
        $this->expectException(TooManyRequestsHttpException::class);
        $rateLimit = $this->createMock(RateLimit::class);
        $rateLimit
            ->expects(self::once())
            ->method('isAccepted')
            ->willReturn(false);

        $rateLimiter = $this->createMock(RateLimiterFactory::class);
        $kernel = $this->createMock(KernelInterface::class);

        $limiter = $this->createMock(LimiterInterface::class);
        $limiter
            ->expects(self::once())
            ->method('consume')
            ->with(1)
            ->willReturn($rateLimit);

        $request = $this->createMock(Request::class);
        $request
            ->expects(self::once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1');

        $kernel
            ->expects(self::once())
            ->method('isDebug')
            ->willReturn(false);

        $rateLimiter
            ->expects(self::once())
            ->method('create')
            ->with('127.0.0.1')
            ->willReturn($limiter);

        $event = new RequestEvent($kernel, $request, 1);
        $subscriber = new RateLimiterSubscriber($kernel, $rateLimiter);
        $subscriber->onKernelRequest($event);
    }
}
