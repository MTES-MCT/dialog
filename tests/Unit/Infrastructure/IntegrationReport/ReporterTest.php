<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\IntegrationReport;

use App\Domain\User\Organization;
use App\Infrastructure\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\IntegrationReport\RecordTypeEnum;
use App\Infrastructure\IntegrationReport\Reporter;
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

        $this->logger
            ->expects(self::exactly(16))
            ->method('log')
            ->withConsecutive(
                [LogLevel::INFO, 'started'],
                [LogLevel::INFO, CommonRecordEnum::FACT_START_TIME->value, ['value' => '2024-08-01T16:00:00+0000']],
                [LogLevel::INFO, CommonRecordEnum::FACT_INTEGRATION_NAME->value, ['value' => 'test']],
                [LogLevel::INFO, CommonRecordEnum::FACT_ORGANIZATION->value, ['value' => ['uuid' => '066eacf1-1029-72da-8000-692c061b2d79', 'name' => 'Test org']]],
                [LogLevel::DEBUG, 'request', ['method' => 'GET', 'path' => '/example', 'options' => ['headers' => ['Accept-Encoding' => 'gzip']]]],
                [LogLevel::DEBUG, 'response', ['status' => 200]],
                [LogLevel::ERROR, 'some_error', ['message' => 'oops!']],
                [LogLevel::WARNING, 'some_warning', ['message' => 'beware!']],
                [LogLevel::DEBUG, 'some_notice', ['msg' => 'here is some info']],
                [LogLevel::INFO, 'some_count', ['value' => 42, '%filter%' => 'endDate IS NULL']],
                [LogLevel::INFO, 'extract:done', []],
                [LogLevel::DEBUG, 'extract:done:details', ['result' => 'some_result']],
                [LogLevel::INFO, 'end'],
                [LogLevel::INFO, CommonRecordEnum::FACT_END_TIME->value, ['value' => '2024-08-01T16:02:00+0000']],
                [LogLevel::INFO, CommonRecordEnum::FACT_ELAPSED_SECONDS->value, ['value' => 120]],
                [LogLevel::INFO, 'report', ['content' => 'report content']],
            );

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
