<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventSubscriber;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\EmptyRoadBanIdException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\IntersectionGeocodingFailureException;
use App\Application\Exception\LaneGeocodingFailureException;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\Exception\RoadGeocodingFailureException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
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
        // Les exceptions de sécurité sont gérées par le composant Security
        // (réponses 401/403), on ne les intercepte pas ici.
        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException) {
            return null;
        }

        if ($exception instanceof EmptyRoadBanIdException) {
            $this->logger->error(
                'Empty roadBanId in the command GetNamedStreetGeometryQuery',
                [
                    'exception' => $exception->getMessage(),
                ],
            );
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

        // Exceptions HTTP (404, 405, 429...) : on conserve le code de statut
        // mais on renvoie une réponse JSON cohérente avec le reste de l'API.
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();

            return new JsonResponse([
                'status' => $statusCode,
                'detail' => Response::$statusTexts[$statusCode] ?? 'Error',
            ], $statusCode, $exception->getHeaders());
        }

        // Toute autre exception : réponse générique sans détail interne
        // (pas de page HTML ni de stack trace côté API).
        $this->logger->error('Unhandled API exception', [
            'exception' => $exception,
        ]);

        return new JsonResponse([
            'status' => 500,
            'detail' => 'Internal Server Error',
        ], 500);
    }

    private function getErrorMap(): array
    {
        return [
            [IntersectionGeocodingFailureException::class, $this->translator->trans('regulation.location.error.intersection_not_found', [], 'validators')],
            [LaneGeocodingFailureException::class, $this->translator->trans('regulation.location.error.lane_geocoding_failed', [], 'validators')],
            [AbscissaOutOfRangeException::class, $this->translator->trans('regulation.location.error.abscissa_out_of_range', [], 'validators')],
            [RoadGeocodingFailureException::class, $this->translator->trans('regulation.location.error.numbered_road_geocoding_failed', [], 'validators')],
            [GeocodingFailureException::class, $this->translator->trans('regulation.location.error.geocoding_failed', [], 'validators')],
            [OrganizationCannotInterveneOnGeometryException::class, $this->translator->trans('regulation.location.error.organization_cannot_intervene_on_geometry', ['%organizationName%' => null], 'validators')],
        ];
    }
}
