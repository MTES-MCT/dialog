<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Application\StringUtilsInterface;
use Symfony\Component\Form\FormError;

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
            new \Twig\TwigFunction('app_time', [$this, 'formatTime']),
            new \Twig\TwigFunction('app_number', [$this, 'formatNumber']),
            new \Twig\TwigFunction('app_is_client_past_day', [$this, 'isClientPastDay']),
            new \Twig\TwigFunction('app_is_client_future_day', [$this, 'isClientFutureDay']),
            new \Twig\TwigFunction('app_vehicle_type_icon_name', [$this, 'getVehicleTypeIconName']),
            new \Twig\TwigFunction('app_is_fieldset_error', [$this, 'isFieldsetError']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('app_capfirst', [$this, 'capFirst']),
        ];
    }

    /**
     * Format a $date with an optional $time
     */
    public function formatDateTime(\DateTimeInterface $date, \DateTimeInterface|bool|null $time = null): string
    {
        $dateTime = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone);
        $format = 'd/m/Y';

        if ($time) {
            $time = \DateTimeImmutable::createFromInterface($time === true ? $date : $time)->setTimezone($this->clientTimezone);
            $dateTime = $dateTime->setTime((int) $time->format('H'), (int) $time->format('i'), (int) $time->format('s'));
            $format = 'd/m/Y à H\hi';
        }

        return $dateTime->format($format);
    }

    public function formatTime(\DateTimeInterface $time): string
    {
        // SUBTLE: Copy current date to have same daylight saving time (DST) period
        $now = new \DateTimeImmutable('now');

        return \DateTimeImmutable::createFromInterface($time)
            ->setTimeZone($this->clientTimezone)
            ->setDate((int) $now->format('Y'), (int) $now->format('m'), (int) $now->format('d'))
            ->format('H\hi');
    }

    public function formatNumber(float $value, ?int $decimals = null): string
    {
        if ($decimals === null) {
            if (filter_var($value, FILTER_VALIDATE_INT)) {
                // It's an int
                $decimals = 0;
            } else {
                // Detect number of decimals
                // Source: https://stackoverflow.com/a/2430214
                $strValue = (string) $value;
                $decimals = \strlen($strValue) - strpos($strValue, '.') - 1;
            }
        }

        return number_format($value, $decimals, ',', ' ');
    }

    public function isClientPastDay(\DateTimeInterface $date, ?\DateTimeInterface $today = null): bool
    {
        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('now');
        $today = $today->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        $day = \DateTimeImmutable::createFromInterface($date)->setTimeZone($this->clientTimezone)->setTime(0, 0, 0, 0);

        return $day < $today;
    }

    public function isClientFutureDay(\DateTimeInterface $date, ?\DateTimeInterface $today = null): bool
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

    public function isFieldsetError(FormError $error, string $fieldset): bool
    {
        $payload = $error->getCause()->getConstraint()->payload;

        return $payload && \array_key_exists('fieldset', $payload) && $payload['fieldset'] === $fieldset;
    }

    public function capFirst(string $text): \Twig\Markup
    {
        if (!$text) {
            return new \Twig\Markup('', 'utf-8');
        }

        return new \Twig\Markup(strtoupper(substr($text, 0, 1)) . substr($text, 1), 'utf-8');
    }
}
