<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

final class EudonetParisExecutionReportBuilder
{
    private int $numProcessed;
    private int $numCreated;
    private int $numSkipped;
    private array $messages;
    private ?string $error;

    public function __construct()
    {
        $this->numProcessed = 0;
        $this->numCreated = 0;
        $this->numSkipped = 0;
        $this->messages = [];
        $this->error = null;
    }

    public function addCreated(): void
    {
        ++$this->numProcessed;
        ++$this->numCreated;
    }

    public function addSkip(array $messages): void
    {
        ++$this->numProcessed;
        ++$this->numSkipped;
        array_push($this->messages, ...$messages);
    }

    public function setError(string $message): void
    {
        $this->error = $message;
    }

    public function build(): EudonetParisExecutionReport
    {
        return new EudonetParisExecutionReport(
            numProcessed: $this->numProcessed,
            numCreated: $this->numCreated,
            numSkipped: $this->numSkipped,
            messages: $this->messages,
            error: $this->error,
        );
    }
}
