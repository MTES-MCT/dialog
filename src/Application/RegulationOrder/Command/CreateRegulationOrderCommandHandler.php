<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Command;

use App\Application\IdFactoryInterface;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateRegulationOrderCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private ValidatorInterface $validator,
        private RegulationOrderRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateRegulationOrderCommand $command): string
    {
        $errors = $this->validator->validate($command);

        if (\count($errors) > 0) {
            throw new ValidationFailedException(null, $errors);
        }

        $obj = $this->repository->save(
            new RegulationOrder(
                uuid: $this->idFactory->make(),
                description: $command->description,
                issuingAuthority: $command->issuingAuthority,
            ),
        );

        return $obj->getUuid();
    }
}
