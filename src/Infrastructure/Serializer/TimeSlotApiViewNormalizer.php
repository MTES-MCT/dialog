<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Infrastructure\DTO\Regulation\TimeSlotApiView;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Time slot times are stored as UTC times of day corresponding to the client
 * timezone wall clock (see PeriodsTransformer / the IHM TimeType convention).
 * They must be exposed in the client timezone so that the value read back from
 * the API matches both what was submitted and what the IHM displays.
 */
final class TimeSlotApiViewNormalizer implements NormalizerInterface
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        \assert($data instanceof TimeSlotApiView);

        $timezone = new \DateTimeZone($this->clientTimezone);

        return [
            'startTime' => $this->formatTime($data->startTime, $timezone),
            'endTime' => $this->formatTime($data->endTime, $timezone),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof TimeSlotApiView;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [TimeSlotApiView::class => true];
    }

    private function formatTime(?\DateTimeInterface $time, \DateTimeZone $timezone): ?string
    {
        if (!$time) {
            return null;
        }

        return \DateTimeImmutable::createFromInterface($time)
            ->setTimezone($timezone)
            ->format(\DateTimeInterface::ATOM);
    }
}
