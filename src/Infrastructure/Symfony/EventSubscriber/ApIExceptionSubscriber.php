<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

final class ApIExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        // Check if validation exception is nested
        if ($exception instanceof ValidationFailedException) {
            $violations = $exception->getViolations();
            $response = new JsonResponse([
                'status' => 422,
                'detail' => 'Validation failed',
                'violations' => array_map(
                    fn ($violation) => [
                        'propertyPath' => $violation->getPropertyPath(),
                        'title' => $violation->getMessage(),
                        'parameters' => $violation->getParameters(),
                    ],
                    iterator_to_array($violations),
                ),
            ], 422);

            $event->setResponse($response);

            return;
        }
    }
}
