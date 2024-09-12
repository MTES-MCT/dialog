<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\StringUtilsInterface;
use App\Application\User\Command\SaveProfileCommand;
use App\Application\User\Command\SaveProfileCommandHandler;
use App\Domain\User\Exception\EmailAlreadyExistsException;
use App\Domain\User\Specification\IsEmailAlreadyExists;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveProfileCommandHandlerTest extends TestCase
{
    private MockObject $stringUtils;
    private MockObject $isEmailAlreadyExists;

    public function setUp(): void
    {
        $this->stringUtils = $this->createMock(StringUtilsInterface::class);
        $this->isEmailAlreadyExists = $this->createMock(IsEmailAlreadyExists::class);

        $this->stringUtils
            ->expects(self::once())
            ->method('normalizeEmail')
            ->willReturn('lea.lefoulon@beta.gouv.fr');
    }

    public function testUpdateFullNameAndEmail(): void
    {
        $user = $this->createMock(User::class);

        $user
            ->expects(self::exactly(2))
            ->method('getEmail')
            ->willReturn('lea.lefoulon@beta.gouv.com');

        $user
            ->expects(self::once())
            ->method('setFullName')
            ->with('Lea LEFOULON');

        $user
            ->expects(self::once())
            ->method('setEmail')
            ->with('lea.lefoulon@beta.gouv.fr');

        $this->isEmailAlreadyExists
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('lea.lefoulon@beta.gouv.fr')
            ->willReturn(false);

        $handler = new SaveProfileCommandHandler(
            $this->stringUtils,
            $this->isEmailAlreadyExists,
        );
        $command = new SaveProfileCommand($user);
        $command->fullName = 'Lea LEFOULON';
        $command->email = 'lea.lefoulon@beta.gouv.fr';

        $handler($command);
    }

    public function testUpdateEmailAlreadyExist(): void
    {
        $this->expectException(EmailAlreadyExistsException::class);
        $user = $this->createMock(User::class);

        $user
            ->expects(self::exactly(2))
            ->method('getEmail')
            ->willReturn('lea.lefoulon@beta.gouv.com');

        $user
            ->expects(self::never())
            ->method('setFullName');

        $user
            ->expects(self::never())
            ->method('setEmail');

        $this->isEmailAlreadyExists
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('lea.lefoulon@beta.gouv.fr')
            ->willReturn(true);

        $handler = new SaveProfileCommandHandler(
            $this->stringUtils,
            $this->isEmailAlreadyExists,
        );
        $command = new SaveProfileCommand($user);
        $command->fullName = 'Lea LEFOULON';
        $command->email = 'lea.lefoulon@beta.gouv.fr';

        $handler($command);
    }
}
