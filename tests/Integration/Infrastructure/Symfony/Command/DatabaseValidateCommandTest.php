<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

final class DatabaseValidateCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        // Run in the context of PHPUnit so that customizations are included in test coverage.
        // ApplicationTester dispatches console events, which the migrations bundle relies on
        // to exclude its metadata table from the schema comparison.
        $applicationTester = new ApplicationTester($application);
        $applicationTester->run(['command' => 'doctrine:schema:validate']);

        $applicationTester->assertCommandIsSuccessful();
    }
}
