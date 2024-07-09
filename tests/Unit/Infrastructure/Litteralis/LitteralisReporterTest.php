<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Domain\User\Organization;
use App\Infrastructure\Litteralis\LitteralisReporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LitteralisReporterTest extends TestCase
{
    private $logger;
    private $reporter;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->reporter = new LitteralisReporter($this->logger);
    }

    public function testReport(): void
    {
        $organization = $this->createMock(Organization::class);
        $startDate = new \DateTimeImmutable('2024-08-01 16:00');
        $endDate = new \DateTimeImmutable('2024-08-01 16:02');

        $this->logger
            ->expects(self::exactly(14))
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
                [LogLevel::INFO, 'end'],
                [LogLevel::INFO, 'end_time', ['value' => '2024-08-01T16:02:00+0000']],
                [LogLevel::INFO, 'elapsed_seconds', ['value' => 120]],
                [LogLevel::INFO, 'report', ['content' => 'report content']],
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
        $this->reporter->onReport('report content');

        $this->assertEquals([
            [LitteralisReporter::FACT, [LitteralisReporter::FACT => 'start_time', 'value' => '2024-08-01T16:00:00+0000']],
            [LitteralisReporter::ERROR, [LitteralisReporter::ERROR => 'some_error', 'message' => 'oops!']],
            [LitteralisReporter::WARNING, [LitteralisReporter::WARNING => 'some_warning', 'message' => 'beware!']],
            [LitteralisReporter::NOTICE, [LitteralisReporter::NOTICE => 'some_notice', 'msg' => 'here is some info']],
            [LitteralisReporter::COUNT, [LitteralisReporter::COUNT => 'some_count', 'value' => 42, '%filter%' => 'endDate IS NULL']],
            [LitteralisReporter::FACT, [LitteralisReporter::FACT => 'end_time', 'value' => '2024-08-01T16:02:00+0000']],
            [LitteralisReporter::FACT, [LitteralisReporter::FACT => 'elapsed_seconds', 'value' => 120]],
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
