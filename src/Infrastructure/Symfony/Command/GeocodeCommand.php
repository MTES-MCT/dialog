<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\GeocoderInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\LocationAddress;
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
        private RoadGeocoderInterface $roadGeocoder,
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

        if (preg_match('/^\d/', $address)) {
            $coords = $this->geocoder->computeCoordinates($address);
            $point = sprintf('POINT(%.6f %.6f)', $coords->longitude, $coords->latitude);
            $output->writeln($point);

            return Command::SUCCESS;
        }

        $addressObj = LocationAddress::fromString($address);
        $geometry = $this->roadGeocoder->computeRoadLine($addressObj->getRoadName(), '59368');
        $output->writeln($geometry);

        return Command::SUCCESS;
    }
}
