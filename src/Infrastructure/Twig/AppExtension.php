<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Application\StringUtilsInterface;

class AppExtension extends \Twig\Extension\AbstractExtension
{
    private \DateTimeZone $clientTimezone;

    public function __construct(
        string $clientTimezone,
        private StringUtilsInterface $stringUtils,
    ) {
        $this->clientTimezone = new \DateTimeZone($clientTimezone);
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('app_datetime', [$this, 'formatDateTime']),
            new \Twig\TwigFunction('app_is_client_past_day', [$this, 'isClientPastDay']),
            new \Twig\TwigFunction('app_is_client_future_day', [$this, 'isClientFutureDay']),
            new \Twig\TwigFunction('app_vehicle_type_icon_name', [$this, 'getVehicleTypeIconName']),
        ];
    }

    /**
     * Format a $date with an optional $time
     */
    public function formatDateTime(\DateTimeInterface $date, \DateTimeInterface $time = null): string
    {
        $dateTime = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone);
        $format = 'd/m/Y';

        if ($time) {
            $time = \DateTimeImmutable::createFromInterface($time)->setTimezone($this->clientTimezone);
            $dateTime = $dateTime->setTime((int) $time->format('H'), (int) $time->format('i'), (int) $time->format('s'));
            $format = 'd/m/Y à H\hi';
        }

        return $dateTime->format($format);
    }

    public function isClientPastDay(\DateTimeInterface $date, \DateTimeInterface $today = null): bool
    {
        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('now');
        $today = $today->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        $day = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        return $day < $today;
    }

    public function isClientFutureDay(\DateTimeInterface $date, \DateTimeInterface $today = null): bool
    {
        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('now');
        $today = $today->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        $day = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        return $today < $day;
    }

    public function getVehicleTypeIconName(string $value): string
    {
        if ($value === 'other') {
            return '';
        }

        if (str_starts_with($value, 'critair')) {
            return 'critair';
        }

        return $this->stringUtils->toKebabCase($value);
    }
}
