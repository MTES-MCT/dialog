<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Application\StringUtilsInterface;

class AppExtension extends \Twig\Extension\AbstractExtension
{
    public function __construct(
        private StringUtilsInterface $stringUtils,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('app_is_past_day', [$this, 'isPastDay']),
            new \Twig\TwigFunction('app_is_future_day', [$this, 'isFutureDay']),
            new \Twig\TwigFunction('app_vehicle_type_icon_name', [$this, 'getVehicleTypeIconName']),
        ];
    }

    public function isPastDay(\DateTimeInterface $date, \DateTimeInterface $today = null): bool
    {
        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('now');
        $today = $today->setTime(0, 0, 0, 0);

        $day = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0, 0);

        return $day < $today;
    }

    public function isFutureDay(\DateTimeInterface $date, \DateTimeInterface $today = null): bool
    {
        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('now');
        $today = $today->setTime(0, 0, 0, 0);

        $day = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0, 0);

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
