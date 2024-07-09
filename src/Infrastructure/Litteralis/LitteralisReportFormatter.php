<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use Symfony\Contracts\Translation\TranslatorInterface;

final class LitteralisReportFormatter
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

    public function format(array $records): string
    {
        $lines = [];

        // Une entête pour le rapport
        $lines[] = $this->header('=', $this->translator->trans('litteralis.report.title'));
        $lines[] = '';

        // Une section avec les infos d'exécution
        $lines[] = $this->header('-', $this->translator->trans('litteralis.report.section.fact.title'));
        $lines[] = '';

        foreach ($this->findRecordsByType(LitteralisReporter::FACT, $records) as [$name, $context]) {
            $verboseName = $this->translator->trans(\sprintf('litteralis.report.fact.%s', $name));
            $value = $context['value'];

            if ($name === 'elapsed_seconds') {
                $seconds = $value;
                [$minutes, $seconds] = [intdiv($seconds, 60), $seconds % 60];
                $value = \sprintf('%s min %s s', $minutes, $seconds);
            }

            $lines[] = \sprintf('%s : %s', $verboseName, $value);
        }

        $lines[] = '';

        // Une section avec les décomptes
        $lines[] = $this->header('-', $this->translator->trans('litteralis.report.section.count.title'));
        $lines[] = '';

        foreach ($this->findRecordsByType(LitteralisReporter::COUNT, $records) as [$name, $context]) {
            $verboseName = $this->translator->trans(\sprintf('litteralis.report.count.%s', $name));
            $value = $context['value'];
            $lines[] = \sprintf('%s : %s', $verboseName, $value);
        }

        $lines[] = '';

        // On affiche une section par type de "cas" rencontré par le reporter.
        // Au sein de chaque section, on affiche chaque cas rencontré, son nombre d'occurrence, et les arrêtés concernés.

        $caseLists = [
            LitteralisReporter::ERROR => [],
            LitteralisReporter::WARNING => [],
            LitteralisReporter::NOTICE => [],
        ];

        // Pour chaque type de cas, calcul des nombres d'occurrence et arrêtés concernés
        foreach ($records as [$type, $context]) {
            if (!\array_key_exists($type, $caseLists)) {
                continue;
            }

            $name = $context[$type];

            if (!isset($caseLists[$type][$name])) {
                $caseLists[$type][$name] = ['count' => 0, 'regulations' => []];
            }

            $info = $caseLists[$type][$name];

            ++$info['count'];

            if (\array_key_exists('arretesrcid', $context)) {
                $info['regulations'][] = $context['arretesrcid'];
            }

            $caseLists[$type][$name] = $info;
        }

        foreach ($caseLists as $type => $cases) {
            $lines[] = $this->header('-', $this->translator->trans(\sprintf('litteralis.report.section.%s.title', $type)));
            $lines[] = '';

            foreach ($cases as $name => $info) {
                // Affichage du cas et du nombre d'occurrences
                $verboseName = $this->translator->trans(\sprintf('litteralis.report.%s.%s', $type, $name));
                $lines[] = \sprintf('%s : %s', $verboseName, $info['count']);

                if (\count($info['regulations']) > 0) {
                    // Affichage des arrêtés concernés
                    $lines[] = \sprintf('  %s :', $this->translator->trans('litteralis.report.regulations'));

                    foreach ($info['regulations'] as $id) {
                        $lines[] = \sprintf('    %s', $id);
                    }
                }

                $lines[] = '';
            }

            $lines[] = '';
        }

        return $this->merge($lines);
    }
}
