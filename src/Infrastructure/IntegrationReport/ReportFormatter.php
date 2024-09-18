<?php

declare(strict_types=1);

namespace App\Infrastructure\IntegrationReport;

use Symfony\Contracts\Translation\TranslatorInterface;

final class ReportFormatter
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    private function merge(array $lines): string
    {
        return implode(PHP_EOL, $lines);
    }

    private function header(string $char, string $text): string
    {
        $lines = [];
        $lines[] = $text;
        $lines[] = str_repeat($char, \strlen($text));

        return $this->merge($lines);
    }

    private function findRecordsByType(string $theType, array $records): array
    {
        $matchingRecords = [];

        foreach ($records as [$type, $context]) {
            if ($type === $theType) {
                $matchingRecords[] = [$context[$type], $context];
            }
        }

        return $matchingRecords;
    }

    private function arrayEncode(array $array): string
    {
        $items = [];

        foreach ($array as $key => $value) {
            $items[] = implode(': ', [$key, $value]);
        }

        return implode(' ; ', $items);
    }

    public function format(array $records): string
    {
        $lines = [];

        // Une entête pour le rapport
        $lines[] = $this->header('=', $this->translator->trans('integration.report.title'));
        $lines[] = '';

        // Une section avec les infos d'exécution
        $lines[] = $this->header('-', $this->translator->trans('integration.report.section.fact.title'));
        $lines[] = '';

        foreach ($this->findRecordsByType(RecordTypeEnum::FACT->value, $records) as [$name, $context]) {
            $verboseName = $this->translator->trans(\sprintf('integration.report.fact.%s', $name));
            $value = $context['value'];

            if ($name === CommonRecordEnum::FACT_ELAPSED_SECONDS->value) {
                $seconds = $value;
                [$minutes, $seconds] = [intdiv($seconds, 60), $seconds % 60];
                $value = \sprintf('%s min %s s', $minutes, $seconds);
            }

            if (\is_array($value)) {
                $value = $this->arrayEncode($value);
            }

            $lines[] = \sprintf('%s : %s', $verboseName, $value);
        }

        $lines[] = '';

        // Une section avec les décomptes
        $lines[] = $this->header('-', $this->translator->trans('integration.report.section.count.title'));
        $lines[] = '';

        foreach ($this->findRecordsByType(RecordTypeEnum::COUNT->value, $records) as [$name, $context]) {
            $verboseName = $this->translator->trans(\sprintf('integration.report.count.%s', $name));
            $value = $context['value'];
            $line = \sprintf('%s : %s', $verboseName, $value);

            if (\array_key_exists('regulationsCount', $context)) {
                $line = \sprintf(
                    '%s (%s)',
                    $line,
                    $this->translator->trans(
                        'integration.report.among_regulations',
                        [
                            '%count%' => $context['regulationsCount'],
                        ],
                    ),
                );
            }

            $lines[] = $line;
        }

        $lines[] = '';

        // On affiche une section par type de "cas" rencontré par le reporter.
        // Au sein de chaque section, on affiche chaque cas rencontré, son nombre d'occurrence, et les arrêtés concernés.

        $caseLists = [
            RecordTypeEnum::ERROR->value => [],
            RecordTypeEnum::WARNING->value => [],
            RecordTypeEnum::NOTICE->value => [],
        ];

        // Pour chaque type de cas, calcul des nombres d'occurrence et arrêtés concernés
        foreach ($records as [$type, $context]) {
            if (!\array_key_exists($type, $caseLists)) {
                continue;
            }

            $name = $context[$type];

            if (!isset($caseLists[$type][$name])) {
                $caseLists[$type][$name] = ['count' => 0, 'regulations' => [], 'urls' => []];
            }

            $info = $caseLists[$type][$name];

            ++$info['count'];

            $regulationId = $context[CommonRecordEnum::ATTR_REGULATION_ID->value];

            if (!\in_array($regulationId, $info['regulations'])) {
                $info['regulations'][] = $regulationId;

                if (\array_key_exists(CommonRecordEnum::ATTR_URL->value, $context)) {
                    $info['urls'][$regulationId] = $context[CommonRecordEnum::ATTR_URL->value];
                }
            }

            $caseLists[$type][$name] = $info;
        }

        foreach ($caseLists as $type => $cases) {
            $lines[] = $this->header('-', $this->translator->trans(\sprintf('integration.report.section.%s.title', $type)));
            $lines[] = '';

            foreach ($cases as $name => $info) {
                // Affichage du cas et du nombre d'occurrences
                $verboseName = $this->translator->trans(\sprintf('integration.report.%s.%s', $type, $name));

                $lines[] = \sprintf(
                    '%s : %s (%s)',
                    $verboseName,
                    $info['count'],
                    $this->translator->trans(
                        'integration.report.among_regulations',
                        [
                            '%count%' => \count($info['regulations']),
                        ],
                    ),
                );

                // Affichage des arrêtés concernés
                $lines[] = \sprintf('  %s :', $this->translator->trans('integration.report.regulations'));

                foreach ($info['regulations'] as $id) {
                    $line = \sprintf('    %s', $id);

                    if (\array_key_exists($id, $info['urls'])) {
                        $url = $info['urls'][$id];
                        $line = \sprintf('%s (%s)', $line, $url);
                    }

                    $lines[] = $line;
                }

                $lines[] = '';
            }

            $lines[] = '';
        }

        return $this->merge($lines);
    }
}
