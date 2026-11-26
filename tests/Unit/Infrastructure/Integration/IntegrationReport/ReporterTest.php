<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Integration\IntegrationReport;

use App\Domain\User\Organization;
use App\Infrastructure\Integration\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\Integration\IntegrationReport\RecordTypeEnum;
use App\Infrastructure\Integration\IntegrationReport\Reporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ReporterTest extends TestCase
{
    private $logger;
    private $reporter;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->reporter = new Reporter($this->logger);
    }

    public function testNoLogger(): void
    {
        // For coverage
        $reporter = new Reporter();
        $this->assertEmpty($reporter->getRecords());
    }

    public function testReport(): void
    {
        $organization = $this->createMock(Organization::class);
        $startDate = new \DateTimeImmutable('2024-08-01 16:00');
        $endDate = new \DateTimeImmutable('2024-08-01 16:02');

        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('066eacf1-1029-72da-8000-692c061b2d79');
        $organization
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Test org');
        $matcher = self::exactly(16);

        $this->logger
            ->expects($matcher)
            ->method('log')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals('started', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals(CommonRecordEnum::FACT_START_TIME->value, $parameters[1]);
                    $this->assertEquals(['value' => '2024-08-01T16:00:00+0000'], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals(CommonRecordEnum::FACT_INTEGRATION_NAME->value, $parameters[1]);
                    $this->assertEquals(['value' => 'test'], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 4) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals(CommonRecordEnum::FACT_ORGANIZATION->value, $parameters[1]);
                    $this->assertEquals(['value' => ['uuid' => '066eacf1-1029-72da-8000-692c061b2d79', 'name' => 'Test org']], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 5) {
                    $this->assertEquals(LogLevel::DEBUG, $parameters[0]);
                    $this->assertEquals('request', $parameters[1]);
                    $this->assertEquals(['method' => 'GET', 'path' => '/example', 'options' => ['headers' => ['Accept-Encoding' => 'gzip']]], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 6) {
                    $this->assertEquals(LogLevel::DEBUG, $parameters[0]);
                    $this->assertEquals('response', $parameters[1]);
                    $this->assertEquals(['status' => 200], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 7) {
                    $this->assertEquals(LogLevel::ERROR, $parameters[0]);
                    $this->assertEquals('some_error', $parameters[1]);
                    $this->assertEquals(['message' => 'oops!'], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 8) {
                    $this->assertEquals(LogLevel::WARNING, $parameters[0]);
                    $this->assertEquals('some_warning', $parameters[1]);
                    $this->assertEquals(['message' => 'beware!'], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 9) {
                    $this->assertEquals(LogLevel::DEBUG, $parameters[0]);
                    $this->assertEquals('some_notice', $parameters[1]);
                    $this->assertEquals(['msg' => 'here is some info'], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 10) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals('some_count', $parameters[1]);
                    $this->assertEquals(['value' => 42, '%filter%' => 'endDate IS NULL'], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 11) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals('extract:done', $parameters[1]);
                    $this->assertEquals([], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 12) {
                    $this->assertEquals(LogLevel::DEBUG, $parameters[0]);
                    $this->assertEquals('extract:done:details', $parameters[1]);
                    $this->assertEquals(['result' => 'some_result'], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 13) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals('end', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 14) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals(CommonRecordEnum::FACT_END_TIME->value, $parameters[1]);
                    $this->assertEquals(['value' => '2024-08-01T16:02:00+0000'], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 15) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals(CommonRecordEnum::FACT_ELAPSED_SECONDS->value, $parameters[1]);
                    $this->assertEquals(['value' => 120], $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 16) {
                    $this->assertEquals(LogLevel::INFO, $parameters[0]);
                    $this->assertEquals('report', $parameters[1]);
                    $this->assertEquals(['content' => 'report content'], $parameters[2]);
                }
            });

        $this->assertEquals([], $this->reporter->getRecords());

        $this->reporter->start('test', $startDate, $organization);
        $this->reporter->onRequest('GET', '/example', ['headers' => ['Accept-Encoding' => 'gzip']]);
        $this->reporter->onResponse(new MockResponse());
        $this->reporter->addError('some_error', ['message' => 'oops!']);
        $this->reporter->addWarning('some_warning', ['message' => 'beware!']);
        $this->reporter->addNotice('some_notice', ['msg' => 'here is some info']);
        $this->reporter->addCount('some_count', 42, ['%filter%' => 'endDate IS NULL']);
        $this->reporter->onExtract('some_result');
        $this->reporter->end($endDate);
        $this->reporter->onReport('report content');

        $this->assertEquals([
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_START_TIME->value, 'value' => '2024-08-01T16:00:00+0000']],
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_INTEGRATION_NAME->value, 'value' => 'test']],
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_ORGANIZATION->value, 'value' => ['uuid' => '066eacf1-1029-72da-8000-692c061b2d79', 'name' => 'Test org']]],
            [RecordTypeEnum::ERROR->value, [RecordTypeEnum::ERROR->value => 'some_error', 'message' => 'oops!']],
            [RecordTypeEnum::WARNING->value, [RecordTypeEnum::WARNING->value => 'some_warning', 'message' => 'beware!']],
            [RecordTypeEnum::NOTICE->value, [RecordTypeEnum::NOTICE->value => 'some_notice', 'msg' => 'here is some info']],
            [RecordTypeEnum::COUNT->value, [RecordTypeEnum::COUNT->value => 'some_count', 'value' => 42, '%filter%' => 'endDate IS NULL']],
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_END_TIME->value, 'value' => '2024-08-01T16:02:00+0000']],
            [RecordTypeEnum::FACT->value, [RecordTypeEnum::FACT->value => CommonRecordEnum::FACT_ELAPSED_SECONDS->value, 'value' => 120]],
        ], $this->reporter->getRecords());
    }

    public function testErrorTracking(): void
    {
        $this->assertFalse($this->reporter->hasNewErrors());
        $this->reporter->addWarning('warn');
        $this->assertFalse($this->reporter->hasNewErrors());
        $this->reporter->addError('error');
        $this->assertTrue($this->reporter->hasNewErrors());
        $this->reporter->acknowledgeNewErrors();
        $this->assertFalse($this->reporter->hasNewErrors());
    }
}
