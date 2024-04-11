<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Symfony\Component\HttpFoundation\Request;

class FeatureFlagService
{
    public function __construct(
        private array $featureMap,
    ) {
    }

    public function isFeatureEnabled(string $featureName, ?Request $request = null): bool
    {
        $queryParam = 'feature_' . $featureName;

        if ($request && $request->query->has($queryParam)) {
            return $request->query->getBoolean($queryParam);
        }

        return \array_key_exists($featureName, $this->featureMap) && $this->featureMap[$featureName];
    }
}
