<?php

declare(strict_types=1);

namespace App\Application\VisaModel\Command;

use App\Domain\VisaModel\Exception\VisaModelNotFoundException;
use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;
use App\Domain\VisaModel\VisaModel;

final class DeleteVisaModelCommandHandler
{
    public function __construct(
        private VisaModelRepositoryInterface $visaModelRepository,
    ) {
    }

    public function __invoke(DeleteVisaModelCommand $command): void
    {
        $visaModel = $this->visaModelRepository->findOneByUuid($command->uuid);
        if (!$visaModel instanceof VisaModel) {
            throw new VisaModelNotFoundException();
        }

        $this->visaModelRepository->remove($visaModel);
    }
}
