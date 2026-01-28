<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\EventSubscriber;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\EmptyRoadBanIdException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\LaneGeocodingFailureException;
use App\Application\Exception\OrganizationCannotInterveneOnGeometryException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Infrastructure\Symfony\EventSubscriber\ApIExceptionSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApIExceptionSubscriberTest extends TestCase
{
    private MockObject|ApIExceptionSubscriber $subscriber;
    private MockObject|TranslatorInterface $translator;
    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new ApIExceptionSubscriber($this->translator, $this->logger);
    }

    private function setupTranslatorMock(): void
    {
        $this->translator
            ->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function (string $key, array $parameters = []) {
                $translations = [
                    'regulation.location.error.lane_geocoding_failed' => 'Lane geocoding failed',
                    'regulation.location.error.abscissa_out_of_range' => 'Abscissa out of range',
                    'regulation.location.error.numbered_road_geocoding_failed' => 'Road geocoding failed',
                    'regulation.location.error.geocoding_failed' => 'Geocoding failed',
                    'regulation.location.error.organization_cannot_intervene_on_geometry' => 'Organization cannot intervene',
                ];

                return $translations[$key] ?? $key;
            });
    }

    public function testGetSubscribedEvents(): void
    {
        $expected = [
            'kernel.exception' => ['onKernelException', 0],
        ];
        $this->assertEquals($expected, ApIExceptionSubscriber::getSubscribedEvents());
    }

    public function testNonApiRequestIsIgnored(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/web/path');

        $exception = new \Exception('Some error');
        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testEmptyRoadBanIdExceptionIsLogged(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/api/test');

        $exception = new EmptyRoadBanIdException();
        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Empty roadBanId in the command GetNamedStreetGeometryQuery',
                ['exception' => ''],
            );

        $this->subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testValidationFailedExceptionReturnsJson(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/api/test');

        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getPropertyPath')->willReturn('email');
        $violation->method('getMessage')->willReturn('Invalid email');
        $violation->method('getParameters')->willReturn([]);

        $violations = new ConstraintViolationList([$violation]);
        $exception = new ValidationFailedException(new \stdClass(), $violations);

        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(422, $data['status']);
        $this->assertEquals('Validation failed', $data['detail']);
        $this->assertCount(1, $data['violations']);
        $this->assertEquals('email', $data['violations'][0]['propertyPath']);
        $this->assertEquals('Invalid email', $data['violations'][0]['title']);
    }

    public function testLaneGeocodingFailureExceptionReturns400(): void
    {
        $this->setupTranslatorMock();

        $subscriber = new ApIExceptionSubscriber($this->translator, $this->logger);

        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/api/test');

        $exception = new LaneGeocodingFailureException('Lane error');
        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Lane geocoding failed', $data['detail']);
    }

    public function testAbscissaOutOfRangeExceptionReturns400(): void
    {
        $this->setupTranslatorMock();

        $subscriber = new ApIExceptionSubscriber($this->translator, $this->logger);

        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/api/test');

        $exception = new AbscissaOutOfRangeException('Out of range');
        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Abscissa out of range', $data['detail']);
    }

    public function testRoadGeocodingFailureExceptionReturns400(): void
    {
        $this->setupTranslatorMock();

        $subscriber = new ApIExceptionSubscriber($this->translator, $this->logger);

        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/api/test');

        $exception = new RoadGeocodingFailureException('Road error');
        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Road geocoding failed', $data['detail']);
    }

    public function testGeocodingFailureExceptionReturns400(): void
    {
        $this->setupTranslatorMock();

        $subscriber = new ApIExceptionSubscriber($this->translator, $this->logger);

        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/api/test');

        $exception = new GeocodingFailureException('Geocoding error');
        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Geocoding failed', $data['detail']);
    }

    public function testOrganizationCannotInterveneOnGeometryExceptionReturns400(): void
    {
        $this->setupTranslatorMock();

        $subscriber = new ApIExceptionSubscriber($this->translator, $this->logger);

        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/api/test');

        $exception = new OrganizationCannotInterveneOnGeometryException('Cannot intervene');
        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Organization cannot intervene', $data['detail']);
    }

    public function testUnhandledExceptionReturnsNull(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/api/test');

        $exception = new \RuntimeException('Unhandled error');
        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testMultipleViolationsInValidationException(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getPathInfo')->willReturn('/api/test');

        $violation1 = $this->createMock(ConstraintViolation::class);
        $violation1->method('getPropertyPath')->willReturn('email');
        $violation1->method('getMessage')->willReturn('Invalid email');
        $violation1->method('getParameters')->willReturn([]);

        $violation2 = $this->createMock(ConstraintViolation::class);
        $violation2->method('getPropertyPath')->willReturn('password');
        $violation2->method('getMessage')->willReturn('Too short');
        $violation2->method('getParameters')->willReturn([]);

        $violations = new ConstraintViolationList([$violation1, $violation2]);
        $exception = new ValidationFailedException(new \stdClass(), $violations);

        $event = new ExceptionEvent($kernel, $request, KernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['violations']);
        $this->assertEquals('email', $data['violations'][0]['propertyPath']);
        $this->assertEquals('password', $data['violations'][1]['propertyPath']);
    }
}
