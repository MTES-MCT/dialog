<?php

declare(strict_types=1);

namespace App\Application\Regulation;

use App\Application\DateUtilsInterface;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\PublicHolidayCalendar;

final class PeriodPublicHolidaysProvider
{
    // Horizon de calcul des jours fériés pour une période sans date de fin
    private const OPEN_ENDED_HORIZON = '+1 year';

    public function __construct(
        private DateUtilsInterface $dateUtils,
        private string $clientTimezone,
    ) {
    }

    /**
     * Jours fériés exclus de la période, en tenant compte de ses jours d'application.
     *
     * @param bool $limitToUpcoming Pour une période sans date de fin, borne également le début du
     *                              calcul à aujourd'hui (horizon « 12 prochains mois »). À passer à
     *                              false lorsqu'un découpage déterministe, indépendant de la date de
     *                              consultation, est requis (ex. identifiants CIFS stables).
     *
     * @return \DateTimeImmutable[] Débuts des jours fériés (minuit, fuseau client), triés
     */
    public function getExcludedPublicHolidays(Period $period, bool $limitToUpcoming = true): array
    {
        if (!$period->isPublicHolidaysExcluded()) {
            return [];
        }

        $timezone = new \DateTimeZone($this->clientTimezone);
        $now = $this->dateUtils->getNow();
        $start = \DateTimeImmutable::createFromInterface($period->getStartDateTime() ?? $now)->setTimezone($timezone);

        if ($period->getEndDateTime()) {
            $end = \DateTimeImmutable::createFromInterface($period->getEndDateTime())->setTimezone($timezone);
        } else {
            // Période sans date de fin : on borne l'horizon à [aujourd'hui, aujourd'hui + 1 an] pour
            // ne pas inclure tout l'historique des jours fériés déjà passés.
            $nowInClientTz = $now->setTimezone($timezone);
            $end = $nowInClientTz->modify(self::OPEN_ENDED_HORIZON);

            if ($limitToUpcoming && $start < $nowInClientTz) {
                $start = $nowInClientTz;
            }
        }

        $applicableDays = $period->getDailyRange()?->getApplicableDays() ?? [];
        $departmentCode = $period->getMeasure()->getRegulationOrder()->getRegulationOrderRecord()?->getOrganization()?->getDepartmentCode();
        $holidays = [];

        for ($year = (int) $start->format('Y'); $year <= (int) $end->format('Y'); ++$year) {
            foreach (PublicHolidayCalendar::getDates($year, $departmentCode) as $date) {
                $holidayStart = new \DateTimeImmutable($date, $timezone);
                $holidayEnd = $holidayStart->modify('+1 day');

                if ($holidayEnd <= $start || $holidayStart >= $end) {
                    continue;
                }

                if ($applicableDays && !\in_array(ApplicableDayEnum::getByIndex((int) $holidayStart->format('N') - 1), $applicableDays, true)) {
                    continue;
                }

                $holidays[] = $holidayStart;
            }
        }

        return $holidays;
    }
}
