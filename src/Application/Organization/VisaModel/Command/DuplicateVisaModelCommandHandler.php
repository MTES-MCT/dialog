<?php

declare(strict_types=1);

namespace App\Application\Organization\VisaModel\Command;

use App\Application\CommandBusInterface;
use App\Domain\Organization\VisaModel\Exception\VisaModelNotFoundException;
use App\Domain\Organization\VisaModel\Repository\VisaModelRepositoryInterface;
use App\Domain\Organization\VisaModel\VisaModel;

final class DuplicateVisaModelCommandHandler
{
    public function __construct(
        private VisaModelRepositoryInterface $visaModelRepository,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DuplicateVisaModelCommand $command): void
    {
        $orginalVisaModel = $this->visaModelRepository->findOneByUuid($command->uuid);
        if (!$orginalVisaModel instanceof VisaModel) {
            throw new VisaModelNotFoundException();
        }

        $visaModelCommand = new SaveVisaModelCommand($command->organization);
        $visaModelCommand->name = \sprintf('%s (copie)', $orginalVisaModel->getName());
        $visaModelCommand->description = $orginalVisaModel->getDescription();
        $visaModelCommand->visas = $orginalVisaModel->getVisas();

        $this->commandBus->handle($visaModelCommand);
    }
}
