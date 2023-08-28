<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

final class EudonetParisExecutionReport
{
    private float $percentCreated;
    private float $percentSkipped;
    private array $lines;

    public function __construct(
        private int $numProcessed,
        private int $numCreated,
        private int $numSkipped,
        private array $messages,
        private ?string $error,
    ) {
        $this->percentCreated = $numProcessed > 0 ? 100 * $numCreated / $numProcessed : 0;
        $this->percentSkipped = $numProcessed > 0 ? 100 * $numSkipped / $numProcessed : 0;
        $this->lines = $this->makeLines();
        $this->lines[] = ''; // Ensure newline
    }

    private function makeLines(): array
    {
        if ($this->hasError()) {
            return ["ERROR: $this->error"];
        }

        return [
            "Processed: $this->numProcessed",
            sprintf('Created: %d (%.1f %%)', $this->numCreated, $this->percentCreated),
            sprintf('Skipped: %d (%.1f %%)', $this->numSkipped, $this->percentSkipped),
            'Messages:',
            ...$this->messages,
        ];
    }

    public function hasError(): bool
    {
        return !\is_null($this->error);
    }

    public function getContent(): string
    {
        return implode("\n", $this->lines);
    }

    public function getLines(): array
    {
        return $this->lines;
    }
}
