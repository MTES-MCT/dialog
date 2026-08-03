<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\User\Command\Mail\SendInactivityReminderEmailCommand;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:send-inactivity-reminders',
    description: 'Envoie un unique mail de relance aux utilisateurs inscrits depuis X jours dont aucun arrêté n\'a été publié dans leurs organisations',
)]
class SendInactivityRemindersCommand extends Command
{
    private const DEFAULT_DAYS = 7;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CommandBusInterface $commandBus,
        private EntityManagerInterface $entityManager,
        private DateUtilsInterface $dateUtils,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'days',
            null,
            InputOption::VALUE_REQUIRED,
            'Nombre de jours d\'inactivité après l\'inscription avant l\'envoi du mail',
            self::DEFAULT_DAYS,
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int) $input->getOption('days');
        $registeredBefore = $this->dateUtils->addDays($this->dateUtils->getNow(), -$days);

        $users = $this->userRepository->findUsersToNotifyForInactivity($registeredBefore);

        if (\count($users) === 0) {
            $output->writeln('Aucun utilisateur à relancer.');

            return Command::SUCCESS;
        }

        $sentCount = 0;

        foreach ($users as $user) {
            try {
                $this->commandBus->dispatchAsync(new SendInactivityReminderEmailCommand($user->getEmail()));
                $user->setInactivityEmailSentAt($this->dateUtils->getNow());
                ++$sentCount;
            } catch (\Throwable $e) {
                $this->logger->error('Échec de l\'envoi du mail de relance d\'inactivité', [
                    'userUuid' => $user->getUuid(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->entityManager->flush();

        $output->writeln(\sprintf('%d mail(s) de relance envoyé(s) sur %d utilisateur(s) éligible(s).', $sentCount, \count($users)));

        return Command::SUCCESS;
    }
}
