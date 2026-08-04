<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\User\Command\Mail\SendInactivityReminderEmailCommand;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Symfony\Command\SendInactivityRemindersCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class SendInactivityRemindersCommandTest extends TestCase
{
    private MockObject|UserRepositoryInterface $userRepository;
    private MockObject|CommandBusInterface $commandBus;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|DateUtilsInterface $dateUtils;
    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function createCommand(): SendInactivityRemindersCommand
    {
        return new SendInactivityRemindersCommand(
            $this->userRepository,
            $this->commandBus,
            $this->entityManager,
            $this->dateUtils,
            $this->logger,
        );
    }

    public function testExecuteWithNoUsers(): void
    {
        $now = new \DateTimeImmutable('2024-05-20 10:00:00');
        $registeredBefore = new \DateTimeImmutable('2024-05-13 10:00:00');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->dateUtils
            ->expects(self::once())
            ->method('addDays')
            ->with($now, -7)
            ->willReturn($registeredBefore);

        $this->userRepository
            ->expects(self::once())
            ->method('findUsersToNotifyForInactivity')
            ->with($registeredBefore)
            ->willReturn([]);

        $this->commandBus
            ->expects(self::never())
            ->method('dispatchAsync');

        $this->entityManager
            ->expects(self::never())
            ->method('flush');

        $command = $this->createCommand();
        $this->assertSame('app:user:send-inactivity-reminders', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Aucun utilisateur à relancer.', $commandTester->getDisplay());
    }

    public function testExecuteWithUsers(): void
    {
        $now = new \DateTimeImmutable('2024-05-20 10:00:00');
        $registeredBefore = new \DateTimeImmutable('2024-05-10 10:00:00');

        $this->dateUtils
            ->method('getNow')
            ->willReturn($now);

        $this->dateUtils
            ->expects(self::once())
            ->method('addDays')
            ->with($now, -10)
            ->willReturn($registeredBefore);

        $user1 = (new User('2763d3b7-4d27-4e81-a2b3-98a466634218'))
            ->setFullName('Antoine Smagghe')
            ->setEmail('antoine.smagghe@fairness.coop');
        $user2 = (new User('55c61cf8-4248-45c1-bfcb-03ed29ba5572'))
            ->setFullName('John Doe')
            ->setEmail('john@example.com');

        $this->userRepository
            ->expects(self::once())
            ->method('findUsersToNotifyForInactivity')
            ->with($registeredBefore)
            ->willReturn([$user1, $user2]);

        $dispatchedEmails = [];
        $this->commandBus
            ->expects(self::exactly(2))
            ->method('dispatchAsync')
            ->willReturnCallback(function (SendInactivityReminderEmailCommand $command) use (&$dispatchedEmails): void {
                $dispatchedEmails[] = $command->email;
            });

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $command = $this->createCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--days' => 10]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertSame(['antoine.smagghe@fairness.coop', 'john@example.com'], $dispatchedEmails);
        $this->assertSame($now, $user1->getInactivityEmailSentAt());
        $this->assertSame($now, $user2->getInactivityEmailSentAt());
        $this->assertStringContainsString('2 mail(s) de relance envoyé(s) sur 2 utilisateur(s) éligible(s).', $commandTester->getDisplay());
    }

    public function testExecuteLogsErrorAndContinuesOnFailure(): void
    {
        $now = new \DateTimeImmutable('2024-05-20 10:00:00');
        $registeredBefore = new \DateTimeImmutable('2024-05-13 10:00:00');

        $this->dateUtils
            ->method('getNow')
            ->willReturn($now);

        $this->dateUtils
            ->method('addDays')
            ->willReturn($registeredBefore);

        $failingUser = (new User('58273e3b-07e1-4d14-8fb5-1e24c32382e2'))
            ->setFullName('Antoine Smagghe')
            ->setEmail('antoine.smagghe@fairness.coop');
        $okUser = (new User('c4dcd39f-49df-49af-81ec-edc76d5730f9'))
            ->setFullName('John Doe')
            ->setEmail('john@example.com');

        $this->userRepository
            ->expects(self::once())
            ->method('findUsersToNotifyForInactivity')
            ->willReturn([$failingUser, $okUser]);

        $this->commandBus
            ->expects(self::exactly(2))
            ->method('dispatchAsync')
            ->willReturnCallback(function (SendInactivityReminderEmailCommand $command): void {
                if ($command->email === 'antoine.smagghe@fairness.coop') {
                    throw new \RuntimeException('Transport failure');
                }
            });

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Échec de l\'envoi du mail de relance d\'inactivité',
                [
                    'userUuid' => '58273e3b-07e1-4d14-8fb5-1e24c32382e2',
                    'error' => 'Transport failure',
                ],
            );

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $command = $this->createCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertNull($failingUser->getInactivityEmailSentAt());
        $this->assertSame($now, $okUser->getInactivityEmailSentAt());
        $this->assertStringContainsString('1 mail(s) de relance envoyé(s) sur 2 utilisateur(s) éligible(s).', $commandTester->getDisplay());
    }
}
