<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

class AppExtension extends \Twig\Extension\AbstractExtension
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('app_datetime', [$this, 'formatDateTime']),
            new \Twig\TwigFunction('app_is_future', [$this, 'isFuture']),
        ];
    }

    /**
     * Format a $date with an optional $time
     */
    public function formatDateTime(\DateTimeInterface $date, ?\DateTimeInterface $time = null): string
    {
        if ($time) {
            $format = 'd/m/Y à H\hi';
            $dateTime = new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
        } else {
            $format = 'd/m/Y';
            $dateTime = new \DateTimeImmutable($date->format('Y-m-d'));
        }

        return $dateTime->setTimezone(new \DateTimeZone($this->clientTimezone))->format($format);
    }

    public function isFuture(\DateTimeInterface $date, \DateTimeInterface $time = null, \DateTimeInterface $reference = null): bool
    {
        if (!$reference) {
            $reference = new \DateTimeImmutable('now');
        }

        if ($time) {
            $date = new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
        }

        return $reference < $date;
    }
}
