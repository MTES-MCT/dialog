<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Application\DateUtilsInterface;
use App\Infrastructure\Adapter\DateUtils;

final class DateUtilsMock implements DateUtilsInterface
{
    private $realDateUtils;

    public function __construct(string $clientTimezone)
    {
        $this->realDateUtils = new DateUtils($clientTimezone);
    }

    public function getTomorrow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2023-05-10');
    }

    public function getNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2023-06-09');
    }

    public function getMicroTime(): float
    {
        return 1695218778.6387;
    }

    public function mergeDateAndTime(\DateTimeInterface $date, \DateTimeInterface $time): \DateTimeInterface
    {
        // Purement un calcul, pas de valeur dépendante de la date et heure de lancement des tests, donc on utilise
        // la vraie méthode.
        return $this->realDateUtils->mergeDateAndTime($date, $time);
    }

    public function addDays(\DateTimeInterface $dateTime, int $numDays): \DateTimeInterface
    {
        return new \DateTimeImmutable('2023-06-10');
    }
}
