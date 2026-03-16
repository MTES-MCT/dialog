<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\EventSubscriber;

use App\Application\CommandBusInterface;
use App\Application\Statistics\Command\RecordApiUsageCommand;
use App\Infrastructure\Symfony\EventSubscriber\ApiUsageRecorderSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final class ApiUsageRecorderSubscriberTest extends TestCase
{
    private MockObject&CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testSubscribedEvents(): void
    {
        self::assertSame(
            [
                KernelEvents::RESPONSE => ['onKernelResponse', 0],
            ],
            ApiUsageRecorderSubscriber::getSubscribedEvents(),
        );
    }

    public function testDispatchesCifsCommandWhenPathIsCifsAndResponse2xx(): void
    {
        $request = Request::create('/api/regulations/cifs.xml');
        $response = new Response('', 200);
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::equalTo(new RecordApiUsageCommand('cifs')));

        $subscriber = new ApiUsageRecorderSubscriber($this->commandBus);
        $subscriber->onKernelResponse($event);
    }

    public function testDispatchesDatexCommandWhenPathIsRegulationsAndResponse2xx(): void
    {
        $request = Request::create('/api/regulations.xml');
        $response = new Response('', 200);
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::equalTo(new RecordApiUsageCommand('datex')));

        $subscriber = new ApiUsageRecorderSubscriber($this->commandBus);
        $subscriber->onKernelResponse($event);
    }

    public function testDispatchesWebCommandWhenPostRegulationsAndResponse2xx(): void
    {
        $request = Request::create('/api/regulations', 'POST');
        $response = new Response('', 200);
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::equalTo(new RecordApiUsageCommand('web')));

        $subscriber = new ApiUsageRecorderSubscriber($this->commandBus);
        $subscriber->onKernelResponse($event);
    }

    public function testDispatchesWebCommandWhenPutRegulationsPublishAndResponse2xx(): void
    {
        $request = Request::create('/api/regulations/publish/F2025/001', 'PUT');
        $response = new Response('', 200);
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::equalTo(new RecordApiUsageCommand('web')));

        $subscriber = new ApiUsageRecorderSubscriber($this->commandBus);
        $subscriber->onKernelResponse($event);
    }

    public function testDispatchesWebCommandWhenPathIsOtherApiAndResponse2xx(): void
    {
        $request = Request::create('/api/stats');
        $response = new Response('', 200);
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::equalTo(new RecordApiUsageCommand('web')));

        $subscriber = new ApiUsageRecorderSubscriber($this->commandBus);
        $subscriber->onKernelResponse($event);
    }

    public function testDispatchesWebCommandWhenMethodNotGetAndRegulationsPath(): void
    {
        $request = Request::create('/api/regulations.xml', 'DELETE');
        $response = new Response('', 200);
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::equalTo(new RecordApiUsageCommand('web')));

        $subscriber = new ApiUsageRecorderSubscriber($this->commandBus);
        $subscriber->onKernelResponse($event);
    }

    public function testDoesNotDispatchWhenPathIsNotApi(): void
    {
        $request = Request::create('/web/foo');
        $response = new Response('', 200);
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $subscriber = new ApiUsageRecorderSubscriber($this->commandBus);
        $subscriber->onKernelResponse($event);
    }

    public function testDoesNotDispatchWhenResponseIsNot2xx(): void
    {
        $request = Request::create('/api/regulations/cifs.xml');
        $response = new Response('', 404);
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $subscriber = new ApiUsageRecorderSubscriber($this->commandBus);
        $subscriber->onKernelResponse($event);
    }
}
