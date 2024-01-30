<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Symfony\Component\HttpFoundation\Request;

class FeatureFlagService
{
    public function __construct(private array $featureMap)
    {
    }

    private function isTruthy(?string $value): bool
    {
        return $value && strtolower($value) !== 'false';
    }

    public function isFeatureEnabled(string $featureName, Request $request = null): bool
    {
        $queryParam = 'feature_' . $featureName;

        if ($request && $request->query->has($queryParam)) {
            return $this->isTruthy($request->query->get($queryParam));
        }

        return \array_key_exists($featureName, $this->featureMap) && $this->isTruthy($this->featureMap[$featureName]);
    }
}
