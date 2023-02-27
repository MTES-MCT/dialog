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
        $dateTime = \DateTime::createFromInterface($date);
        $format = 'd/m/Y';

        if ($time) {
            $dateTime->setTime((int) $time->format('H'), (int) $time->format('i'), (int) $time->format('s'));
            $format = 'd/m/Y à H\hi';
        }

        return $dateTime->setTimezone(new \DateTimeZone($this->clientTimezone))->format($format);
    }

    public function isFuture(\DateTimeInterface $date, \DateTimeInterface $time = null, \DateTimeInterface $reference = null): bool
    {
        if (!$reference) {
            $reference = new \DateTimeImmutable('now');
        }

        $dateTime = \DateTime::createFromInterface($date);

        if ($time) {
            $dateTime->setTime((int) $time->format('H'), (int) $time->format('i'), (int) $time->format('s'));
        }

        return $reference < $dateTime;
    }
}
