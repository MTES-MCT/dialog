<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\DependencyInjection;

use App\Application\Regulation\DTO\CifsFilterSet;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class CifsFilterSetEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv): CifsFilterSet
    {
        /** @var string */
        $env = $getEnv($name);

        if (!$env) {
            return new CifsFilterSet();
        }

        try {
            $value = json_decode($env, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(
                \sprintf('cifs_filterset: value "%s" for env var %s is not valid JSON: %s', $env, $name, $e->getMessage()),
                previous: $e,
            );
        }

        return new CifsFilterSet(
            allowedSources: $value['allowed_sources'] ?? [],
            excludedIdentifiers: $value['excluded_identifiers'] ?? [],
            allowedLocationIds: $value['allowed_location_ids'] ?? [],
            excludedOrgUuids: $value['excluded_org_uuids'] ?? [],
        );
    }

    public static function getProvidedTypes(): array
    {
        return [
            'cifs_filterset' => 'string',
        ];
    }
}
