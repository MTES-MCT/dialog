<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Exception\AdministrativeBoundaryNotFoundException;
use App\Application\Exception\AdministrativeBoundaryUnavailableException;
use App\Application\Geography\AdministrativeBoundaryResolver;
use App\Domain\Geography\Repository\AdministrativeBoundaryRepositoryInterface;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:administrative-boundaries:refresh',
    description: 'Refreshes the administrative_boundary table: re-downloads the official boundary (COG) of every local authority already stored, so that the filters by local authority follow the yearly changes of the COG.',
    hidden: false,
)]
final class RefreshAdministrativeBoundariesCommand extends Command
{
    public function __construct(
        private readonly AdministrativeBoundaryRepositoryInterface $administrativeBoundaryRepository,
        private readonly AdministrativeBoundaryResolver $administrativeBoundaryResolver,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $numRefreshed = 0;
        $numRemoved = 0;
        $numFailed = 0;

        foreach ($this->administrativeBoundaryRepository->findAllViews() as $boundary) {
            try {
                $this->administrativeBoundaryResolver->refresh(OrganizationCodeTypeEnum::from($boundary->codeType), $boundary->code);
                ++$numRefreshed;
            } catch (AdministrativeBoundaryNotFoundException) {
                // La collectivité n'existe plus dans le COG (fusion de communes, EPCI dissous...).
                $this->administrativeBoundaryRepository->delete($boundary->codeType, $boundary->code);
                ++$numRemoved;
            } catch (AdministrativeBoundaryUnavailableException $exc) {
                // On conserve le contour existant : il sera rafraîchi à la prochaine exécution.
                $output->writeln(\sprintf('<error>%s</error>', $exc->getMessage()));
                ++$numFailed;
            }
        }

        $output->writeln(\sprintf(
            '<info>Administrative boundaries: %d refreshed, %d removed, %d failed.</info>',
            $numRefreshed,
            $numRemoved,
            $numFailed,
        ));

        return $numFailed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
