<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

abstract class AbstractBrowserTest extends PantherTestCase
{
    protected static array $defaultOptions = [
        'browser' => self::FIREFOX,
    ];
}
