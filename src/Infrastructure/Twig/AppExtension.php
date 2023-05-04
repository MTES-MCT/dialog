<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Symfony\Component\String\UnicodeString;

class AppExtension extends \Twig\Extension\AbstractExtension
{
    private \DateTimeZone $clientTimezone;

    public function __construct(
        string $clientTimezone,
    ) {
        $this->clientTimezone = new \DateTimeZone($clientTimezone);
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('app_datetime', [$this, 'formatDateTime']),
            new \Twig\TwigFunction('app_is_client_past_day', [$this, 'isClientPastDay']),
            new \Twig\TwigFunction('app_is_client_future_day', [$this, 'isClientFutureDay']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('app_regulation_general_info_title', [$this, 'getRegulationGeneralInfoTitle']),
        ];
    }

    /**
     * Format a $date with an optional $time
     */
    public function formatDateTime(\DateTimeInterface $date, ?\DateTimeInterface $time = null): string
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

    public function getRegulationGeneralInfoTitle(string $description): string
    {
        return (new UnicodeString($description))->split('. ')[0]->truncate(32, '...', false)->toString();
    }
}
