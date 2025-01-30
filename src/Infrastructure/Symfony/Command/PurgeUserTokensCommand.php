<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\User\Repository\TokenRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user_tokens:purge',
    description: 'Purge user tokens that have expired',
    hidden: false,
)]
class PurgeUserTokensCommand extends Command
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->tokenRepository->deleteExpiredTokens();

        return Command::SUCCESS;
    }
}
