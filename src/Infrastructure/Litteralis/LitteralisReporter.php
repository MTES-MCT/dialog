<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use App\Domain\User\Organization;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class LitteralisReporter
{
    public const COUNT = 'count';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const FACT = 'fact';

    // Un COUNT est un nombre total de quelque chose
    public const COUNT_TOTAL_FEATURES = 'total_features';
    public const COUNT_MATCHING_FEATURES = 'matching_features';
    public const COUNT_EXTRACTED_FEATURES = 'extracted_features';

    // Un FACT est une donnée quelconque que l'on souhaite afficher dans le rapport
    public const FACT_START_TIME = 'start_time';
    public const FACT_END_TIME = 'end_time';
    public const FACT_ELAPSED_TIME = 'elapsed_time';

    // Si une ERROR est rencontrée, l'arrêté ne devrait pas être importé sinon il ne serait pas fidèle aux données.
    public const ERROR_REGULATION_START_DATE_PARSING_FAILED = 'regulation_start_date_parsing_failed';
    public const ERROR_REGULATION_END_DATE_PARSING_FAILED = 'regulation_end_date_parsing_failed';
    public const ERROR_MEASURE_PARAMETER_INCONSISTENT_NUMBER = 'measure_parameter_inconsistent_number';
    public const ERROR_MAX_SPEED_VALUE_INVALID = 'max_speed_value_invalid';
    public const ERROR_MAX_SPEED_VALUE_MISSING = 'max_speed_value_missing';
    public const ERROR_DATE_PARSING_FAILED = 'date_parsing_failed';
    public const ERROR_PERIOD_UNPARSABLE = 'period_unparsable';
    public const ERROR_IMPORT_COMMAND_FAILED = 'import_command_failed';

    // Si un WARNING est rencontré, des incohérences ont été détectées dans la donnée source, mais elles
    // n'empêchent pas d'importer l'arrêté.
    public const WARNING_MISSING_GEOMETRY = 'missing_geometry';

    // Un DEBUG fournit toute autre information complémentaire qui n'a pas d'incidence
    // sur ce qui va être importé.
    public const NOTICE_UNSUPPORTED_MEASURE = 'unsupported_measure';
    public const NOTICE_NO_MEASURES_FOUND = 'no_measures_found';

    private bool $_hasNewErrors;
    private \DateTimeInterface $startTime;
    private array $records;

    public function __construct(
        private LoggerInterface $logger,
    ) {
        $this->_hasNewErrors = false;
        $this->records = [];
    }

    private function pushRecord(string $level, string $message, array $context = []): void
    {
        // Enregistrement pour le rapport final
        $recordName = $level;
        $recordContext = [$level => $message, ...$context];
        $this->records[] = [$recordName, $recordContext];

        // On appelle aussi le logger pour un affichage plus "temps réel" dans le fichier de log.
        $logLevel = match ($level) {
            self::ERROR => LogLevel::ERROR,
            self::WARNING => LogLevel::WARNING,
            self::NOTICE => LogLevel::DEBUG,
            self::COUNT => LogLevel::INFO,
            self::FACT => LogLevel::INFO,
            default => throw new \LogicException(\sprintf('Unknown level: %s', $level)),
        };

        $this->logger->log($logLevel, $message, $context);
    }

    /**
     * Renvoie true si 'addError()' a été appelé depuis la création du reporter ou depuis le dernier rappel de 'acknowledgeNewErrors()'.
     */
    public function hasNewErrors(): bool
    {
        return $this->_hasNewErrors;
    }

    public function acknowledgeNewErrors(): void
    {
        $this->_hasNewErrors = false;
    }

    public function addError(string $name, array $context = []): void
    {
        $this->pushRecord(self::ERROR, $name, $context);
        $this->_hasNewErrors = true;
    }

    public function addWarning(string $name, array $context = []): void
    {
        $this->pushRecord(self::WARNING, $name, $context);
    }

    public function addNotice(string $name, array $context = []): void
    {
        $this->pushRecord(self::NOTICE, $name, $context);
    }

    public function setCount(string $name, float $value, array $context = []): void
    {
        $this->pushRecord(self::COUNT, $name, ['value' => $value, ...$context]);
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function start(\DateTimeInterface $startTime, Organization $organization): void
    {
        $this->startTime = $startTime;
        $this->logger->log('info', 'started', ['organization' => ['uuid' => $organization->getUuid(), 'name' => $organization->getName()]]);
        $this->pushRecord(self::FACT, 'start_time', ['value' => $startTime->format(\DateTimeInterface::ISO8601)]);
    }

    public function end(\DateTimeInterface $endTime): void
    {
        $elapsedSeconds = $endTime->getTimestamp() - $this->startTime->getTimestamp();
        $this->logger->log('info', 'end');
        $this->pushRecord(self::FACT, 'end_time', ['value' => $endTime->format(\DateTimeInterface::ISO8601)]);
        $this->pushRecord(self::FACT, 'elapsed_seconds', ['value' => $elapsedSeconds]);
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
