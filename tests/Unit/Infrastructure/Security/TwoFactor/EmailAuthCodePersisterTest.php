<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security\TwoFactor;

use App\Application\DateUtilsInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Security\TwoFactor\EmailAuthCodePersister;
use App\Infrastructure\Security\User\PasswordUser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class EmailAuthCodePersisterTest extends TestCase
{
    public function testPersistStoresCodeAndExpiry(): void
    {
        $now = new \DateTimeImmutable('2024-01-01 10:00:00');

        $securityUser = $this->createMock(PasswordUser::class);
        $securityUser->expects(self::once())->method('getUserIdentifier')->willReturn('mathieu@fairness.coop');
        $securityUser->expects(self::once())->method('getEmailAuthCode')->willReturn('123456');

        $domainUser = $this->createMock(User::class);
        $domainUser->expects(self::once())->method('setEmailAuthCode')->with('123456');
        $domainUser->expects(self::once())->method('setEmailAuthCodeExpiresAt')->with(new \DateTimeImmutable('2024-01-01 10:15:00'));

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects(self::once())->method('findOneByEmail')->with('mathieu@fairness.coop')->willReturn($domainUser);

        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $dateUtils->expects(self::once())->method('getNow')->willReturn($now);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        (new EmailAuthCodePersister($userRepository, $dateUtils, $entityManager, 15))->persist($securityUser);
    }

    public function testPersistDoesNothingWhenUserNotFound(): void
    {
        $securityUser = $this->createMock(PasswordUser::class);
        $securityUser->method('getUserIdentifier')->willReturn('unknown@fairness.coop');

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects(self::once())->method('findOneByEmail')->willReturn(null);

        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        (new EmailAuthCodePersister($userRepository, $dateUtils, $entityManager, 15))->persist($securityUser);
    }

    public function testPersistIgnoresUnsupportedUser(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects(self::never())->method('findOneByEmail');

        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $unsupportedUser = new \stdClass();

        (new EmailAuthCodePersister($userRepository, $dateUtils, $entityManager, 15))->persist($unsupportedUser);
    }
}
