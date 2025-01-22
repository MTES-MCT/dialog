<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\TokenGenerator;
use PHPUnit\Framework\TestCase;

final class TokenGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $tokenGenerator = new TokenGenerator();

        $this->assertNotEmpty($tokenGenerator->generate());
        $this->assertSame(43, \strlen($tokenGenerator->generate()));
    }
}
