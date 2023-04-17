<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

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
            new \Twig\TwigFunction('app_is_future', [$this, 'isFuture']),
            new \Twig\TwigFunction('app_is_client_past_day', [$this, 'isClientPastDay']),
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

    public function isFuture(\DateTimeInterface $date = null, \DateTimeInterface $time = null, \DateTimeInterface $reference = null): bool
    {
        if (!$date) {
            $date = new \DateTimeImmutable('now');

            $date = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone);
            $date = $date->setTime(0,0,0,0);
           
        }

        if (!$reference) {
            $reference = new \DateTimeImmutable('now');
            $reference = \DateTimeImmutable::createFromInterface($reference)->setTimeZone($this->clientTimezone);
        }
        else{
            $reference = \DateTimeImmutable::createFromInterface($reference)->setTimeZone($this->clientTimezone);
            }
       
        $dateTime = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone);
        
        if ($time) {
            $dateTime = $dateTime->setTime((int) $time->format('H'), (int) $time->format('i'), (int) $time->format('s'));
        }
    
        return $reference < $dateTime;
        
    }

    public function isClientPastDay(\DateTimeInterface $date, \DateTimeInterface $today = null)
    {
        if (!$today) {
            $today = (new \DateTimeImmutable('now'))->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);
        }

        $day = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        return $day < $today;
    }
}
