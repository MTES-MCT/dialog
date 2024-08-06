<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Specification\IsEmailAlreadyExists;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class IsEmailAlreadyExistsTest extends TestCase
{
    public function testEmailAlreadyExist(): void
    {
        $user = $this->createMock(User::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu.marchois@beta.gouv.fr')
            ->willReturn($user);

        $pattern = new IsEmailAlreadyExists($userRepository);
        $this->assertTrue($pattern->isSatisfiedBy('mathieu.marchois@beta.gouv.fr'));
    }

    public function testEmailDoesntExist(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu.marchois@beta.gouv.fr')
            ->willReturn(null);

        $pattern = new IsEmailAlreadyExists($userRepository);
        $this->assertFalse($pattern->isSatisfiedBy('mathieu.marchois@beta.gouv.fr'));
    }
}
