<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\GeocoderInterface;
use App\Domain\Geography\GeometryFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:geocode',
    description: 'Geocode an address like the server would',
    hidden: false,
)]
class GeocodeCommand extends Command
{
    public function __construct(
        private GeocoderInterface $geocoder,
        private GeometryFormatter $geometryFormatter,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument('address', InputArgument::REQUIRED, 'The address as a string');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $address = $input->getArgument('address');

        $coords = $this->geocoder->computeCoordinates($address);
        $point = $this->geometryFormatter->formatPoint($coords->latitude, $coords->longitude);
        $output->writeln($point);

        return Command::SUCCESS;
    }
}
