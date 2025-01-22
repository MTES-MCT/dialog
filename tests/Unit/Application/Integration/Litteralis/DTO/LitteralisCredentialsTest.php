<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Integration\Litteralis\DTO;

use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use PHPUnit\Framework\TestCase;

final class LitteralisCredentialsTest extends TestCase
{
    public function testModel(): void
    {
        $credentials = new LitteralisCredentials();
        $this->assertNull($credentials->getOrgId('test'));
        $this->assertNull($credentials->getCredentials('test'));

        $credentials->add('test', 'f9757e4a-d543-4277-9643-074ffbf7069c', 'user:pass');
        $this->assertSame('f9757e4a-d543-4277-9643-074ffbf7069c', $credentials->getOrgId('test'));
        $this->assertSame('user:pass', $credentials->getCredentials('test'));
    }
}
