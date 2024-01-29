<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\FeatureFlagService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class FeatureFlagServiceTest extends TestCase
{
    private function provideIsFeatureEnabled(): array
    {
        return [
            'disabled-env-not-present' => [
                'env' => [],
                'featureName' => 'EXAMPLE',
                'request' => null,
                'expected' => false,
            ],
            'disabled-env-empty' => [
                'env' => ['APP_FEATURE_EXAMPLE_ENABLED' => ''],
                'featureName' => 'EXAMPLE',
                'request' => null,
                'expected' => false,
            ],
            'disabled-env-other-featyre' => [
                'env' => ['APP_FEATURE_OTHER_ENABLED' => 'true'],
                'featureName' => 'EXAMPLE',
                'request' => null,
                'expected' => false,
            ],
            'disabled-env-false' => [
                'env' => ['APP_FEATURE_EXAMPLE_ENABLED' => 'false'],
                'featureName' => 'EXAMPLE',
                'request' => null,
                'expected' => false,
            ],
            'disabled-env-false-case-insensitive' => [
                'env' => ['APP_FEATURE_EXAMPLE_ENABLED' => 'FaLse'],
                'featureName' => 'EXAMPLE',
                'request' => null,
                'expected' => false,
            ],
            'enabled-by-env' => [
                'env' => ['APP_FEATURE_EXAMPLE_ENABLED' => 'true'],
                'featureName' => 'EXAMPLE',
                'request' => null,
                'expected' => true,
            ],
            'enabled-by-env-truthy' => [
                'env' => ['APP_FEATURE_EXAMPLE_ENABLED' => 'something'],
                'featureName' => 'EXAMPLE',
                'request' => null,
                'expected' => true,
            ],
            'enabled-by-request' => [
                'env' => [],
                'featureName' => 'EXAMPLE',
                'request' => new Request(query: ['APP_FEATURE_EXAMPLE_ENABLED' => 'true']),
                'expected' => true,
            ],
            'disabled-by-request' => [
                'env' => ['APP_FEATURE_EXAMPLE_ENABLED' => 'true'],
                'featureName' => 'EXAMPLE',
                'request' => new Request(query: ['APP_FEATURE_EXAMPLE_ENABLED' => 'false']),
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideIsFeatureEnabled
     */
    public function testIsFeatureEnabled(array $env, string $featureName, ?Request $request, bool $expected): void
    {
        $featureFlagService = new FeatureFlagService($env);

        $this->assertSame($expected, $featureFlagService->isFeatureEnabled($featureName, $request));
    }
}
