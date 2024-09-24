<?php

declare(strict_types=1);

namespace App\Infrastructure\IntegrationReport;

use App\Domain\User\Organization;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class Reporter
{
    private bool $haveNewErrorsBeenReceived;
    private \DateTimeInterface $startTime;
    private array $records;

    public function __construct(
        private LoggerInterface $logger,
    ) {
        $this->haveNewErrorsBeenReceived = false;
        $this->records = [];
    }

    private function pushRecord(RecordTypeEnum $type, string $message, array $context = []): void
    {
        // Enregistrement pour le rapport final
        $recordContext = [$type->value => $message, ...$context];
        $this->records[] = [$type->value, $recordContext];

        // On appelle aussi le logger pour un affichage plus "temps réel" dans le fichier de log.
        $logLevel = match ($type->value) {
            RecordTypeEnum::ERROR->value => LogLevel::ERROR,
            RecordTypeEnum::WARNING->value => LogLevel::WARNING,
            RecordTypeEnum::NOTICE->value => LogLevel::DEBUG,
            RecordTypeEnum::COUNT->value => LogLevel::INFO,
            RecordTypeEnum::FACT->value => LogLevel::INFO,
        };

        $this->logger->log($logLevel, $message, $context);
    }

    /**
     * Renvoie true si 'addError()' a été appelé depuis la création du reporter ou depuis le dernier rappel de 'acknowledgeNewErrors()'.
     */
    public function hasNewErrors(): bool
    {
        return $this->haveNewErrorsBeenReceived;
    }

    public function acknowledgeNewErrors(): void
    {
        $this->haveNewErrorsBeenReceived = false;
    }

    public function addError(string $name, array $context = []): void
    {
        $this->pushRecord(RecordTypeEnum::ERROR, $name, $context);
        $this->haveNewErrorsBeenReceived = true;
    }

    public function addWarning(string $name, array $context = []): void
    {
        $this->pushRecord(RecordTypeEnum::WARNING, $name, $context);
    }

    public function addNotice(string $name, array $context = []): void
    {
        $this->pushRecord(RecordTypeEnum::NOTICE, $name, $context);
    }

    public function addCount(string $name, float $value, array $context = []): void
    {
        $this->pushRecord(RecordTypeEnum::COUNT, $name, ['value' => $value, ...$context]);
    }

    public function addFact(string $name, mixed $value, array $context = []): void
    {
        $this->pushRecord(RecordTypeEnum::FACT, $name, ['value' => $value, ...$context]);
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function start(\DateTimeInterface $startTime, Organization $organization): void
    {
        $this->startTime = $startTime;
        $this->logger->log('info', 'started', ['organization' => ['uuid' => $organization->getUuid(), 'name' => $organization->getName()]]);
        $this->addFact(CommonRecordEnum::FACT_START_TIME->value, $startTime->format(\DateTimeInterface::ISO8601));
    }

    public function end(\DateTimeInterface $endTime): void
    {
        $elapsedSeconds = $endTime->getTimestamp() - $this->startTime->getTimestamp();
        $this->logger->log('info', 'end');
        $this->addFact(CommonRecordEnum::FACT_END_TIME->value, $endTime->format(\DateTimeInterface::ISO8601));
        $this->addFact(CommonRecordEnum::FACT_ELAPSED_SECONDS->value, $elapsedSeconds);
    }

    public function onRequest(string $method, string $path, array $options): void
    {
        $this->logger->log('debug', 'request', ['method' => $method, 'path' => $path, 'options' => $options]);
    }

    public function onResponse(ResponseInterface $response): void
    {
        $this->logger->log('debug', 'response', ['status' => $response->getStatusCode()]);
    }

    public function onExtract(mixed $result): void
    {
        $this->logger->log('info', 'extract:done');
        $this->logger->log('debug', 'extract:done:details', ['result' => $result]);
    }

    public function onReport(string $report): void
    {
        $this->logger->log('info', 'report', ['content' => $report]);
    }
}
