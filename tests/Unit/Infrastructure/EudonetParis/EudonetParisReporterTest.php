<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EudonetParis;

use App\Domain\User\Organization;
use App\Infrastructure\EudonetParis\EudonetParisReporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\Response\MockResponse;

final class EudonetParisReporterTest extends TestCase
{
    private $logger;
    private $reporter;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->reporter = new EudonetParisReporter($this->logger);
    }

    public function testReport(): void
    {
        $organization = $this->createMock(Organization::class);
        $startDate = new \DateTimeImmutable('2024-08-01 16:00');
        $endDate = new \DateTimeImmutable('2024-08-01 16:02');

        $this->logger
            ->expects(self::exactly(13))
            ->method('log')
            ->withConsecutive(
                [LogLevel::INFO, 'started'],
                [LogLevel::INFO, 'start_time', ['value' => '2024-08-01T16:00:00+0000']],
                [LogLevel::DEBUG, 'request', ['method' => 'GET', 'path' => '/example', 'options' => ['headers' => ['Accept-Encoding' => 'gzip']]]],
                [LogLevel::DEBUG, 'response', ['status' => 200]],
                [LogLevel::ERROR, 'some_error', ['message' => 'oops!']],
                [LogLevel::WARNING, 'some_warning', ['message' => 'beware!']],
                [LogLevel::DEBUG, 'some_notice', ['msg' => 'here is some info']],
                [LogLevel::INFO, 'some_count', ['value' => 42, '%filter%' => 'endDate IS NULL']],
                [LogLevel::INFO, 'extract:done', []],
                [LogLevel::DEBUG, 'extract:done:details', ['result' => 'some_result']],
                [LogLevel::INFO, 'end_time', ['value' => '2024-08-01T16:02:00+0000']],
                [LogLevel::INFO, 'elapsed_seconds', ['value' => 120]],
                [LogLevel::INFO, 'done'],
            );

        $this->assertEquals([], $this->reporter->getRecords());

        $this->reporter->start($startDate, $organization);
        $this->reporter->onRequest('GET', '/example', ['headers' => ['Accept-Encoding' => 'gzip']]);
        $this->reporter->onResponse(new MockResponse());
        $this->reporter->addError('some_error', ['message' => 'oops!']);
        $this->reporter->addWarning('some_warning', ['message' => 'beware!']);
        $this->reporter->addNotice('some_notice', ['msg' => 'here is some info']);
        $this->reporter->setCount('some_count', 42, ['%filter%' => 'endDate IS NULL']);
        $this->reporter->onExtract('some_result');
        $this->reporter->end($endDate);

        $this->assertEquals([
            [EudonetParisReporter::FACT, [EudonetParisReporter::FACT => 'start_time', 'value' => '2024-08-01T16:00:00+0000']],
            [EudonetParisReporter::ERROR, [EudonetParisReporter::ERROR => 'some_error', 'message' => 'oops!']],
            [EudonetParisReporter::WARNING, [EudonetParisReporter::WARNING => 'some_warning', 'message' => 'beware!']],
            [EudonetParisReporter::NOTICE, [EudonetParisReporter::NOTICE => 'some_notice', 'msg' => 'here is some info']],
            [EudonetParisReporter::COUNT, [EudonetParisReporter::COUNT => 'some_count', 'value' => 42, '%filter%' => 'endDate IS NULL']],
            [EudonetParisReporter::FACT, [EudonetParisReporter::FACT => 'end_time', 'value' => '2024-08-01T16:02:00+0000']],
            [EudonetParisReporter::FACT, [EudonetParisReporter::FACT => 'elapsed_seconds', 'value' => 120]],
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
