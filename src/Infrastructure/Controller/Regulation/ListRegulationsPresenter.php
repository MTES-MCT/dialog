<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Controller\PresenterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ListRegulationsPresenter implements PresenterInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function present(array $context): array
    {
        $isTemporaryTab = $context['tab'] === 'temporary';
        $isPermanentTab = $context['tab'] === 'permanent';
        $context['temporaryRegulations']->items = $this->presentRegulations($context['temporaryRegulations']->items);
        $context['permanentRegulations']->items = $this->presentRegulations($context['permanentRegulations']->items);

        return [
            'metaTitle' => $this->translator->trans('regulation.list.meta.title'),
            'temporaryRegulations' => $context['temporaryRegulations'],
            'permanentRegulations' => $context['permanentRegulations'],
            'tab' => $context['tab'],
            'pageSize' => $context['pageSize'],
            'page' => $context['page'],
            'isTemporaryTab' => $isTemporaryTab,
            'isPermanentTab' => $isPermanentTab,
            'temporaryTabsPanelClass' => $isTemporaryTab ? 'fr-tabs__panel--selected' : 'fr-tabs__panel--direction-start',
            'permanentTabsPanelClass' => $isPermanentTab ? 'fr-tabs__panel--selected' : 'fr-tabs__panel--direction-end',
            'temporaryButtonAriaSelected' => $isTemporaryTab ? 'true' : 'false',
            'permanentButtonAriaSelected' => $isPermanentTab ? 'true' : 'false',
        ];
    }

    private function presentRegulations(array $items): array
    {
        $regulations = [];

        foreach ($items as $index => $regulation) {
            $location = null;
            $period = null;
            $startDate = $regulation->startDate;
            $endDate = $regulation->endDate;
            $isUpcoming = $startDate ? $this->dateUtils->isClientFutureDay($startDate) : null;
            $hasPassed = $endDate ? $this->dateUtils->isClientPastDay($endDate) : null;

            if ($loc = $regulation->location) {
                if ($loc->roadType === RoadTypeEnum::LANE->value) {
                    $location = sprintf(' %s <br/><b>%s</b>', $loc->cityLabel, $loc->roadName);
                } elseif ($loc->roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value) {
                    $location = sprintf(' %s (%s)<br/>', $loc->administrator, $loc->roadNumber);
                }

                if ($regulation->numLocations > 1) {
                    $location .= sprintf(' <em>%s</em>', $this->translator->trans('regulation.locations.more', ['%count%' => $regulation->numLocations - 1]));
                }
            }

            if ($startDate) {
                if ($endDate) {
                    $period = sprintf(
                        ' %s %s',
                        $this->translator->trans('common.date.from', ['%date%' => $this->dateUtils->formatDateTime($startDate)]),
                        $this->translator->trans('common.date.to', ['%date%' => $this->dateUtils->formatDateTime($endDate)]),
                    );
                } else {
                    $period = $this->translator->trans('common.date.starting', ['%date%' => $this->dateUtils->formatDateTime($startDate)]);
                }

                if ($isUpcoming) {
                    $period .= sprintf('<br/> <b>%s</b>', $this->translator->trans('common.date.upcoming'));
                } elseif ($hasPassed) {
                    $period .= sprintf('<br/> <b>%s</b>', $this->translator->trans('common.date.passed'));
                } else {
                    $period .= sprintf('<br/> <b>%s</b>', $this->translator->trans('common.date.ongoing'));
                }
            }

            $regulations[$index] = [
                'uuid' => $regulation->uuid,
                'identifier' => $regulation->identifier,
                'location' => $location,
                'period' => $period,
                'organizationName' => $regulation->organizationName,
                'status' => $regulation->status,
            ];
        }

        return $regulations;
    }
}
