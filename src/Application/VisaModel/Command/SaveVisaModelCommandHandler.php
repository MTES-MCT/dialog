<?php

declare(strict_types=1);

namespace App\Application\VisaModel\Command;

use App\Application\IdFactoryInterface;
use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;
use App\Domain\VisaModel\VisaModel;

final class SaveVisaModelCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private VisaModelRepositoryInterface $visaModelRepository,
    ) {
    }

    public function __invoke(SaveVisaModelCommand $command): VisaModel
    {
        return $this->visaModelRepository->add(
            new VisaModel(
                uuid: $this->idFactory->make(),
                name: $command->name,
                visas: $command->visas,
                description: $command->description,
                organization: $command->organization,
            ),
        );
    }
}
