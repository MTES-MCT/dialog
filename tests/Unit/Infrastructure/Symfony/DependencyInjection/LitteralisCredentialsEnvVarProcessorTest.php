<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\DependencyInjection;

use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Infrastructure\Symfony\DependencyInjection\LitteralisCredentialsEnvVarProcessor;
use PHPUnit\Framework\TestCase;

final class LitteralisCredentialsEnvVarProcessorTest extends TestCase
{
    private $dialogOrgId = 'a256d12e-8ce4-439f-b895-00ecbc8ddb14';

    public function testProvidedTypes(): void
    {
        $processor = new LitteralisCredentialsEnvVarProcessor(
            $this->dialogOrgId,
            litteralisEnabledOrgs: [],
        );

        $this->assertEquals(['litteralis_credentials' => 'string'], $processor->getProvidedTypes());
    }

    private function doTest(array $enabledOrgs, array $getEnvReturnValues, ?LitteralisCredentials $expectedCredentials = null): void
    {
        $processor = new LitteralisCredentialsEnvVarProcessor(
            $this->dialogOrgId,
            litteralisEnabledOrgs: $enabledOrgs,
        );

        $value = $processor->getEnv(
            prefix: '',
            name: 'APP_LITTERALIS_ORG_',
            getEnv: function (string $name) use (&$getEnvReturnValues): string {
                [$theName, $value] = $getEnvReturnValues[0];
                array_splice($getEnvReturnValues, 0, 1);

                $this->assertSame($theName, $name);

                return $value;
            },
        );

        $this->assertEmpty($getEnvReturnValues);

        $this->assertEquals($expectedCredentials, $value);
    }

    public function testEmptyNoOrgs(): void
    {
        $this->doTest([], [], new LitteralisCredentials());
    }

    public function testEnvValid(): void
    {
        $this->doTest(
            ['test', 'other'],
            [
                ['APP_LITTERALIS_ORG_TEST_ID', '1442d806-559c-41a9-aa4b-cdd18195a38f'],
                ['APP_LITTERALIS_ORG_TEST_CREDENTIALS', 'testuser:testpass'],
                ['APP_LITTERALIS_ORG_OTHER_ID', '__dialog__'],
                ['APP_LITTERALIS_ORG_OTHER_CREDENTIALS', 'otheruser:otherpass'],
            ],
            (new LitteralisCredentials())
                ->add('test', '1442d806-559c-41a9-aa4b-cdd18195a38f', 'testuser:testpass')
                ->add('other', $this->dialogOrgId, 'otheruser:otherpass'),
        );
    }

    public function testGetEnvInvalidOrgIdMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment variable APP_LITTERALIS_ORG_TEST_ID must not be empty');

        $this->doTest(
            ['test'],
            [['APP_LITTERALIS_ORG_TEST_ID', '']],
        );
    }

    public function testGetEnvInvalidCredentialsMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment variable APP_LITTERALIS_ORG_TEST_CREDENTIALS must not be empty');

        $this->doTest(
            ['test'],
            [
                ['APP_LITTERALIS_ORG_TEST_ID', '1442d806-559c-41a9-aa4b-cdd18195a38f'],
                ['APP_LITTERALIS_ORG_TEST_CREDENTIALS', ''],
            ],
        );
    }
}
