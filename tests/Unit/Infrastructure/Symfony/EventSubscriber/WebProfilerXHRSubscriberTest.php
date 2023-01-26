<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\EventSubscriber\WebProfilerXHRSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class WebProfilerXHRSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $this->assertEquals([
            'kernel.response' => [
                ['onKernelResponse', 0],
            ],
        ], WebProfilerXHRSubscriber::getSubscribedEvents());
    }

    public function testEnabled()
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['Turbo-Frame' => 'example']);
        $response = $this->createMock(Response::class);
        $response->headers = new ResponseHeaderBag();
        $event = new ResponseEvent($kernel, $request, 1, $response);

        $kernel->expects(self::once())
            ->method('isDebug')
            ->willReturn(true);

        $subscriber = new WebProfilerXHRSubscriber($kernel);
        $subscriber->onKernelResponse($event);
        $this->assertSame('1', $response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    public function testNotDebug()
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $response->headers = new ResponseHeaderBag();
        $event = new ResponseEvent($kernel, $request, 1, $response);

        $kernel->expects(self::once())
            ->method('isDebug')
            ->willReturn(false);

        $subscriber = new WebProfilerXHRSubscriber($kernel);
        $subscriber->onKernelResponse($event);

        $this->assertFalse($response->headers->has('Symfony-Debug-Toolbar-Replace'));
    }

    public function testNotTurboFrame()
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['X-MyHeader' => 'not Turbo-Frame']);
        $response = $this->createMock(Response::class);
        $response->headers = new ResponseHeaderBag();
        $event = new ResponseEvent($kernel, $request, 1, $response);

        $kernel->expects(self::once())
            ->method('isDebug')
            ->willReturn(true);

        $subscriber = new WebProfilerXHRSubscriber($kernel);
        $subscriber->onKernelResponse($event);

        $this->assertFalse($response->headers->has('Symfony-Debug-Toolbar-Replace'));
    }
}
