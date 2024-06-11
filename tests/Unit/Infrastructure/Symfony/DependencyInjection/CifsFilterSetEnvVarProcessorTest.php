<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\DependencyInjection;

use App\Application\Regulation\DTO\CifsFilterSet;
use App\Infrastructure\Symfony\DependencyInjection\CifsFilterSetEnvVarProcessor;
use PHPUnit\Framework\TestCase;

final class CifsFilterSetEnvVarProcessorTest extends TestCase
{
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new CifsFilterSetEnvVarProcessor();
    }

    public function testGetEnvEmpty(): void
    {
        $value = $this->processor->getEnv('', 'APP_CIFS_FILTERS', fn () => '');
        $this->assertEquals($value, new CifsFilterSet());

        $value = $this->processor->getEnv('', 'APP_CIFS_FILTERS', fn () => '[]');
        $this->assertEquals($value, new CifsFilterSet());

        $value = $this->processor->getEnv('', 'APP_CIFS_FILTERS', fn () => '{}');
        $this->assertEquals($value, new CifsFilterSet());
    }

    public function testGetEnvValid(): void
    {
        $value = $this->processor->getEnv('', 'APP_CIFS_FILTERS', fn () => '{"allowed_sources": ["source1"], "excluded_identifiers": ["identifier1"], "allowed_location_ids": ["locationId1"]}');

        $this->assertEquals(
            $value,
            new CifsFilterSet(
                allowedSources: ['source1'],
                excludedIdentifiers: ['identifier1'],
                allowedLocationIds: ['locationId1'],
            ),
        );
    }

    public function testGetEnvInvalid(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('value "{" for env var APP_CIFS_FILTERS is not valid JSON: Syntax error');

        $this->processor->getEnv('', 'APP_CIFS_FILTERS', fn () => '{');
    }
}
