<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventSubscriber;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\EmptyRoadBanIdException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\LaneGeocodingFailureException;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\Exception\RoadGeocodingFailureException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApIExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$this->isApiRequest($event)) {
            return;
        }

        $response = $this->createApiErrorResponse($exception);

        if ($response instanceof JsonResponse) {
            $event->setResponse($response);
        }
    }

    private function isApiRequest(ExceptionEvent $event): bool
    {
        return str_starts_with($event->getRequest()->getPathInfo(), '/api');
    }

    private function createApiErrorResponse(\Throwable $exception): ?JsonResponse
    {
        if ($exception instanceof EmptyRoadBanIdException) {
            $this->logger->error('Empty roadBanId in the command GetNamedStreetGeometryQuery', [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($exception instanceof ValidationFailedException) {
            return new JsonResponse([
                'status' => 422,
                'detail' => 'Validation failed',
                'violations' => array_map(
                    static fn ($violation) => [
                        'propertyPath' => $violation->getPropertyPath(),
                        'title' => $violation->getMessage(),
                        'parameters' => $violation->getParameters(),
                    ],
                    iterator_to_array($exception->getViolations()),
                ),
            ], 422);
        }

        foreach ($this->getErrorMap() as [$exceptionClass, $detail]) {
            if ($exception instanceof $exceptionClass) {
                return new JsonResponse([
                    'status' => 400,
                    'detail' => $detail,
                ], 400);
            }
        }

        return null;
    }

    private function getErrorMap(): array
    {
        return [
            [LaneGeocodingFailureException::class, $this->translator->trans('regulation.location.error.lane_geocoding_failed', [], 'validators')],
            [AbscissaOutOfRangeException::class, $this->translator->trans('regulation.location.error.abscissa_out_of_range', [], 'validators')],
            [RoadGeocodingFailureException::class, $this->translator->trans('regulation.location.error.numbered_road_geocoding_failed', [], 'validators')],
            [GeocodingFailureException::class, $this->translator->trans('regulation.location.error.geocoding_failed', [], 'validators')],
            [OrganizationCannotInterveneOnGeometryException::class, $this->translator->trans('regulation.location.error.organization_cannot_intervene_on_geometry', ['%organizationName%' => null], 'validators')],
        ];
    }
}
