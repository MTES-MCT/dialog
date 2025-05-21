<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\DeleteNamedStreetCommand;
use App\Application\Regulation\Query\Location\GetLocationByUuidQuery;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SyncNamedStreetRoadBanIdsCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        // Edit an existing named street so that we don't mess with fixtures

        /** @var EntityManagerInterface */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $queryBus = self::getContainer()->get(QueryBusInterface::class);
        $commandBus = self::getContainer()->get(CommandBusInterface::class);

        /** @var Location */
        $location = $queryBus->handle(new GetLocationByUuidQuery(LocationFixture::UUID_TYPICAL));

        // We will replace the named street on this location, delete the existing named street first to avoid unique constraint error
        $commandBus->handle(new DeleteNamedStreetCommand($location->getNamedStreet()));

        // Set the new named street with missing road ban IDs
        $namedStreet = new NamedStreet(
            '01f99d10-8934-44c2-84b9-2f92b17a8a7a',
            $location,
            DirectionEnum::BOTH->value,
            cityCode: '59606',
            cityLabel: 'Valenciennes',
            roadBanId: null,
            roadName: 'Rue des Récollets',
            fromRoadBanId: null,
            fromRoadName: 'Rue de Paris',
            toRoadBanId: null,
            toRoadName: 'Rue des Moulineaux',
        );
        self::getContainer()->get(NamedStreetRepositoryInterface::class)->add($namedStreet);
        $location->setNamedStreet($namedStreet);

        $em->flush();

        // Check data before migration
        $this->assertEquals(
            [['road_name' => 'Rue des Récollets']],
            $em->getConnection()->fetchAllAssociative('SELECT road_name FROM named_street WHERE road_name IS NOT NULL AND road_ban_id IS NULL'),
        );
        $this->assertEquals(
            [['from_road_name' => 'Rue de Paris']],
            $em->getConnection()->fetchAllAssociative('SELECT from_road_name FROM named_street WHERE road_name IS NOT NULL AND road_ban_id IS NULL'),
        );
        $this->assertEquals(
            [['to_road_name' => 'Rue des Moulineaux']],
            $em->getConnection()->fetchAllAssociative('SELECT to_road_name FROM named_street WHERE road_name IS NOT NULL AND road_ban_id IS NULL'),
        );

        $command = $application->find('app:named_street:road_ban_ids:sync');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful($commandTester->getDisplay());
        $rows = array_map(fn ($line) => json_decode($line, true), explode(PHP_EOL, trim($commandTester->getDisplay())));
        $this->assertCount(2, $rows);
        $this->assertSame(2, $rows[0]['num_candidates']);
        $this->assertSame(1, $rows[0]['num_updated']);
        $this->assertSame(0, $rows[0]['num_errors']);
        $this->assertSame('updated', $rows[1]['message']);
        $this->assertSame($namedStreet->getUuid(), $rows[1]['uuid']);

        // Check data after migration
        $this->assertEquals([], $em->getConnection()->fetchAllAssociative('SELECT road_name FROM named_street WHERE road_name IS NOT NULL AND road_ban_id IS NULL'));
        $this->assertEquals([], $em->getConnection()->fetchAllAssociative('SELECT from_road_name FROM named_street WHERE road_name IS NOT NULL AND road_ban_id IS NULL'));
        $this->assertEquals([], $em->getConnection()->fetchAllAssociative('SELECT to_road_name FROM named_street WHERE road_name IS NOT NULL AND road_ban_id IS NULL'));
        // Check the new road ban IDs are correct
        $location = $queryBus->handle(new GetLocationByUuidQuery($location->getUuid()));
        $this->assertSame('59606_3210', $location->getNamedStreet()->getRoadBanId());
        $this->assertSame('59606_2840', $location->getNamedStreet()->getFromRoadBanId());
        $this->assertSame('59606_2700', $location->getNamedStreet()->getToRoadBanId());
    }

    public function testExecuteGeocodingError(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        /** @var EntityManagerInterface */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $queryBus = self::getContainer()->get(QueryBusInterface::class);
        $commandBus = self::getContainer()->get(CommandBusInterface::class);
        /** @var Location */
        $location = $queryBus->handle(new GetLocationByUuidQuery(LocationFixture::UUID_TYPICAL));
        $commandBus->handle(new DeleteNamedStreetCommand($location->getNamedStreet()));
        $namedStreet = new NamedStreet(
            '01f99d10-8934-44c2-84b9-2f92b17a8a7a',
            $location,
            DirectionEnum::BOTH->value,
            cityCode: '59606',
            cityLabel: 'Valenciennes',
            roadBanId: null,
            roadName: 'Rue des Récollets',
            fromRoadBanId: null,
            fromRoadName: 'Rue de DOES NOT EXIST',
            toRoadBanId: null,
            toRoadName: 'Rue des Moulineaux',
        );
        self::getContainer()->get(NamedStreetRepositoryInterface::class)->add($namedStreet);
        $location->setNamedStreet($namedStreet);
        $em->flush();

        $command = $application->find('app:named_street:road_ban_ids:sync');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $this->assertSame($command::FAILURE, $commandTester->getStatusCode(), $commandTester->getDisplay());
        $rows = array_map(fn ($line) => json_decode($line, true), explode(PHP_EOL, trim($commandTester->getDisplay())));
        $this->assertCount(2, $rows);
        $this->assertSame(2, $rows[0]['num_candidates']);
        $this->assertSame(0, $rows[0]['num_updated']);
        $this->assertSame(1, $rows[0]['num_errors']);
        $this->assertSame('geocoding failed', $rows[1]['message']);
        $this->assertSame($namedStreet->getUuid(), $rows[1]['uuid']);
    }
}
