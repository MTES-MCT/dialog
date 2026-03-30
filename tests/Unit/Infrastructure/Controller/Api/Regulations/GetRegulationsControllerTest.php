<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller\Api\Regulations;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use App\Infrastructure\Adapter\DatexGenerator;
use App\Infrastructure\Controller\Api\Regulations\GetRegulationsController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GetRegulationsControllerTest extends TestCase
{
    private \Twig\Environment&MockObject $twig;
    private DateUtilsInterface&MockObject $dateUtils;
    private QueryBusInterface&MockObject $queryBus;
    private DatexGenerator&MockObject $datexGenerator;
    private GetRegulationsController $controller;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(\Twig\Environment::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->datexGenerator = $this->createMock(DatexGenerator::class);

        $this->controller = new GetRegulationsController(
            $this->twig,
            $this->dateUtils,
            $this->queryBus,
            $this->datexGenerator,
        );
    }

    public function testDefaultParamsWithExistingFile(): void
    {
        $tmpDir = sys_get_temp_dir() . '/datex_test_' . uniqid();
        mkdir($tmpDir, 0o755, true);
        $filePath = $tmpDir . '/regulations.xml';
        file_put_contents($filePath, '<xml>cached</xml>');

        $this->datexGenerator
            ->expects(self::once())
            ->method('getDatexFilePath')
            ->willReturn($filePath);

        $this->datexGenerator
            ->expects(self::never())
            ->method('generate');

        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $response = ($this->controller)();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('<xml>cached</xml>', $response->getContent());

        unlink($filePath);
        rmdir($tmpDir);
    }

    public function testDefaultParamsWithMissingFileTriggersGenerate(): void
    {
        $tmpDir = sys_get_temp_dir() . '/datex_test_' . uniqid();
        $filePath = $tmpDir . '/regulations.xml';

        $this->datexGenerator
            ->expects(self::once())
            ->method('getDatexFilePath')
            ->willReturn($filePath);

        $this->datexGenerator
            ->expects(self::once())
            ->method('generate')
            ->willReturnCallback(function () use ($tmpDir, $filePath): void {
                mkdir($tmpDir, 0o755, true);
                file_put_contents($filePath, '<xml>generated</xml>');
            });

        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $response = ($this->controller)();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<xml>generated</xml>', $response->getContent());

        unlink($filePath);
        rmdir($tmpDir);
    }

    public function testCustomFiltersReturnStreamedResponse(): void
    {
        $now = new \DateTimeImmutable('2025-01-01');
        $regulationOrders = ['order1', 'order2'];

        $this->datexGenerator
            ->expects(self::once())
            ->method('getDatexFilePath')
            ->willReturn('/tmp/nonexistent');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo(new GetRegulationOrdersToDatexFormatQuery(
                includePermanent: false,
                includeTemporary: true,
                includeExpired: true,
            )))
            ->willReturn($regulationOrders);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->twig
            ->expects(self::once())
            ->method('display')
            ->with('api/regulations.xml.twig', [
                'publicationTime' => $now,
                'regulationOrders' => $regulationOrders,
            ]);

        $this->datexGenerator
            ->expects(self::never())
            ->method('generate');

        $response = ($this->controller)(
            includePermanent: false,
            includeTemporary: true,
            includeExpired: true,
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/xml; charset=UTF-8', $response->headers->get('Content-Type'));

        // Trigger the callback to ensure twig->display is called
        $response->sendContent();
    }
}
