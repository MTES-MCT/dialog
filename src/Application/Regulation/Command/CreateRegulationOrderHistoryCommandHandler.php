<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Regulation\RegulationOrderHistory;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use App\Infrastructure\Security\AuthenticatedUser;

final class CreateRegulationOrderHistoryCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository,
        private DateUtilsInterface $dateUtils,
        private AuthenticatedUser $authenticatedUser,
    ) {
    }

    public function __invoke(CreateRegulationOrderHistoryCommand $command): void
    {
        // La seule possibilité d'exécuter cette commande sans être authentifié, c'est
        // d'avoir lancé une commande Symfony en ligne de commande.
        // Dans ce cas on n'a pas d'utilisateur connecté donc on ne stocke pas d'historique.
        if (!$this->authenticatedUser->getUser()) {
            return;
        }

        $this->regulationOrderHistoryRepository->add(
            new RegulationOrderHistory(
                uuid: $this->idFactory->make(),
                regulationOrderUuid: $command->regulationOrder->getUuid(),
                userUuid: $this->authenticatedUser->getUser()->getUuid(),
                action: $command->action,
                date: $this->dateUtils->getNow(),
            ),
        );
    }
}
