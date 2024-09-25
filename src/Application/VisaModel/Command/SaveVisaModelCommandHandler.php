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
        if ($visaModel = $command->visaModel) {
            $visaModel->update(
                name: $command->name,
                visas: $command->visas,
                description: $command->description,
            );

            return $visaModel;
        }

        $visaModel = (new VisaModel($this->idFactory->make()))
            ->setName($command->name)
            ->setDescription($command->description)
            ->setVisas($command->visas)
            ->setOrganization($command->organization);

        return $this->visaModelRepository->add($visaModel);
    }
}
