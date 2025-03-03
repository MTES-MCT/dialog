<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\RoadGeocoderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:geocode:pr',
    description: 'Geocode a reference point',
    hidden: false,
)]
class ComputeReferencePointCommand extends Command
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument('roadType');
        $this->addArgument('administrator');
        $this->addArgument('roadNumber');
        $this->addArgument('pointNumber');
        $this->addArgument('departmentCode');
        $this->addArgument('side');
        $this->addArgument('abscissa');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->roadGeocoder->computeReferencePoint(
            $input->getArgument('roadType'),
            $input->getArgument('administrator'),
            $input->getArgument('roadNumber'),
            $input->getArgument('pointNumber'),
            $input->getArgument('departmentCode'),
            $input->getArgument('side'),
            (int) $input->getArgument('abscissa'),
        );

        $output->writeln($result->asGeoJSON());

        return Command::SUCCESS;
    }
}
