<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Application\Regulation\LocationGeometryChecker;

final class CheckLocationQueryHandler
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private LocationGeometryChecker $locationGeometryChecker,
    ) {
    }

    public function __invoke(CheckLocationQuery $query): string
    {
        $command = $query->location;
        $command->clean();
        $roadCommand = $command->getRoadCommand();
        $roadCommand->clean();

        $geometry = $this->queryBus->handle($roadCommand->getGeometryQuery());
        $this->locationGeometryChecker->checkGeometry($roadCommand, $command->organization->getUuid(), $geometry);

        $exceptionCommands = match (true) {
            $roadCommand instanceof SaveWholeCityCommand,
            $roadCommand instanceof SaveZoneCommand,
            $roadCommand instanceof SaveRawGeoJSONCommand => $roadCommand->exceptions,
            default => [],
        };

        foreach ($exceptionCommands as $exceptionCommand) {
            $exceptionGeometry = $exceptionCommand->computedGeometry;

            if ($exceptionGeometry === null && $geometryQuery = $exceptionCommand->getGeometryQuery()) {
                $exceptionGeometry = $this->queryBus->handle($geometryQuery);
            }

            $this->locationGeometryChecker->checkExceptionGeometry($exceptionCommand->roadType, $exceptionGeometry);
        }

        return $geometry;
    }
}
