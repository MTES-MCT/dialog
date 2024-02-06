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
            'disabled-not-present' => [
                'featureMap' => [],
                'featureName' => 'EXAMPLE',
                'request' => null,
                'expected' => false,
            ],
            'disabled-empty' => [
                'featureMap' => ['example' => ''],
                'featureName' => 'example',
                'request' => null,
                'expected' => false,
            ],
            'disabled-other-feature' => [
                'env' => ['other' => true],
                'featureName' => 'example',
                'request' => null,
                'expected' => false,
            ],
            'disabled-false' => [
                'env' => ['example' => false],
                'featureName' => 'example',
                'request' => null,
                'expected' => false,
            ],
            'enabled' => [
                'env' => ['example' => 'true'],
                'featureName' => 'example',
                'request' => null,
                'expected' => true,
            ],
            'enabled-truthy' => [
                'env' => ['example' => 'something'],
                'featureName' => 'example',
                'request' => null,
                'expected' => true,
            ],
            'enabled-by-request' => [
                'env' => [],
                'featureName' => 'example',
                'request' => new Request(query: ['feature_example' => true]),
                'expected' => true,
            ],
            'disabled-by-request' => [
                'env' => ['example' => 'true'],
                'featureName' => 'example',
                'request' => new Request(query: ['feature_example' => false]),
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideIsFeatureEnabled
     */
    public function testIsFeatureEnabled(array $featureMap, string $featureName, ?Request $request, bool $expected): void
    {
        $featureFlagService = new FeatureFlagService($featureMap);

        $this->assertSame($expected, $featureFlagService->isFeatureEnabled($featureName, $request));
    }
}
