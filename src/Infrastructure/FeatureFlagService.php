<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Symfony\Component\HttpFoundation\Request;

class FeatureFlagService
{
    private array $env;

    public function __construct(array $env = null)
    {
        $this->env = $env ?? $_ENV;
    }

    private function isTruthy(?string $value): bool
    {
        return $value && strtolower($value) !== 'false';
    }

    public function isFeatureEnabled(string $featureName, Request $request = null): bool
    {
        $key = 'APP_FEATURE_' . $featureName . '_ENABLED';

        if ($request && $request->query->has($key)) {
            return $this->isTruthy($request->query->get($key));
        }

        return \array_key_exists($key, $this->env) && $this->isTruthy($this->env[$key]);
    }
}
