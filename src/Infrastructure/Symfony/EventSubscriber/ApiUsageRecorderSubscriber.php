<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventSubscriber;

use App\Application\CommandBusInterface;
use App\Application\Statistics\Command\RecordApiUsageCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiUsageRecorderSubscriber implements EventSubscriberInterface
{
    private const PATH_CIFS = '/api/regulations/cifs';

    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        if (!str_starts_with($pathInfo, '/api')) {
            return;
        }

        $status = $event->getResponse()->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return;
        }

        $type = $this->resolveType($pathInfo);
        $this->commandBus->handle(new RecordApiUsageCommand($type));
    }

    private function resolveType(string $pathInfo): string
    {
        if (str_starts_with($pathInfo, self::PATH_CIFS)) {
            return 'cifs';
        }

        if (preg_match('#^/api/regulations(?:\.|$)#', $pathInfo) === 1) {
            return 'datex';
        }

        return 'web';
    }
}
