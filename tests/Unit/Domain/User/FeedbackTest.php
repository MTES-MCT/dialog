<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Feedback;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class FeedbackTest extends TestCase
{
    public function testGetters(): void
    {
        $user = $this->createMock(User::class);
        $feedback = new Feedback('9cebe00d-04d8-48da-89b1-059f6b7bfe44', 'Ceci est un retour', true, $user);

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $feedback->getUuid());
        $this->assertSame($user, $feedback->getUser());
        $this->assertTrue($feedback->isConsentToBeContacted());
        $this->assertSame('Ceci est un retour', $feedback->getContent());
    }
}
