<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\RegulationOrderHistory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderHistoryFixture extends Fixture implements DependentFixtureInterface
{
    public const INDEX_TYPICAL_TO_REMOVE = 1;
    public const UUID_TYPICAL = 'e48cbfff-bb04-428e-9cb0-22456fd7aab6';
    public const UUID_DOES_NOT_EXIST = '3f45e08a-c6b6-4026-86f1-cb8766756ad5';
    public const UUID_PUBLISHED = '59143d8d-d201-4950-be76-f367e39be522';

    public function load(ObjectManager $manager): void
    {
        $createActionRegulationOrderHistory = new RegulationOrderHistory(
            self::UUID_TYPICAL,
            regulationOrderUuid: '06549047-db9d-74bb-8000-754a6f2ff4c3',
            userUuid: '0b507871-8b5e-4575-b297-a630310fc06e',
            action: ActionTypeEnum::CREATE->value,
            new \DateTimeImmutable('2023-03-11 11:51:00'),
        );

        $updateActionRegulationOrderHistory = new RegulationOrderHistory(
            self::UUID_DOES_NOT_EXIST,
            regulationOrderUuid: '06549047-db9d-74bb-8000-754a6f2ff4c3',
            userUuid: '0b507871-8b5e-4575-b297-a630310fc06e',
            action: ActionTypeEnum::UPDATE->value,
            new \DateTimeImmutable('2023-03-11 14:12:00'),
        );

        $publishActionRegulationOrderHistory = new RegulationOrderHistory(
            self::UUID_PUBLISHED,
            regulationOrderUuid: '06549047-db9d-74bb-8000-754a6f2ff4c3',
            userUuid: '0b507871-8b5e-4575-b297-a630310fc06e',
            action: ActionTypeEnum::PUBLISH->value,
            new \DateTime('2023-03-12'),
        );

        $manager->persist($createActionRegulationOrderHistory);
        $manager->persist($updateActionRegulationOrderHistory);

        $this->addReference('createActionRegulationOrderHistory', $createActionRegulationOrderHistory);
        $this->addReference('updateActionRegulationOrderHistory', $updateActionRegulationOrderHistory);
        $this->addReference('publishActionRegulationOrderHistory', $publishActionRegulationOrderHistory);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
            UserFixture::class,
        ];
    }
}
