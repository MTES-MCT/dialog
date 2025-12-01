<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\ReportAddress;
use App\Domain\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ReportAddressFixture extends Fixture implements DependentFixtureInterface
{
    public const UUID_TYPICAL = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
    public const UUID_NUMBERED_ROAD = 'b2c3d4e5-f6a7-8901-bcde-f12345678901';
    public const UUID_NAMED_ROAD = 'c3d4e5f6-a7b8-9012-cdef-123456789012';
    public const UUID_CONTACTED = 'd4e5f6a7-b8c9-0123-def0-234567890123';

    public function load(ObjectManager $manager): void
    {
        /** @var User */
        $department93User = $this->getReference('department93User', User::class);

        $reportAddress1 = new ReportAddress(
            uuid: self::UUID_TYPICAL,
            content: 'Il y a un problème avec cette adresse, la signalisation est absente.',
            roadType: 'Route départementale - D12',
            user: $department93User,
        );
        $reportAddress1->setCreatedAt(new \DateTimeImmutable('2024-11-20 10:30:00'));

        $reportAddress2 = new ReportAddress(
            uuid: self::UUID_NUMBERED_ROAD,
            content: 'La route nationale N12 présente des panneaux de signalisation manquants entre les points 22##122 et 22##123.',
            roadType: 'Route nationale - N12',
            user: $department93User,
        );
        $reportAddress2->setCreatedAt(new \DateTimeImmutable('2024-11-21 14:15:00'));

        $reportAddress3 = new ReportAddress(
            uuid: self::UUID_NAMED_ROAD,
            content: 'Problème de signalisation sur la rue de la Paix à Paris.',
            roadType: 'Paris - Rue de la Paix',
            user: $department93User,
        );
        $reportAddress3->setCreatedAt(new \DateTimeImmutable('2024-11-22 09:00:00'));

        $reportAddress4 = new ReportAddress(
            uuid: self::UUID_CONTACTED,
            content: 'Signalement déjà traité et contact effectué avec l\'utilisateur.',
            roadType: 'Route départementale - D93',
            user: $department93User,
            hasBeenContacted: true,
        );
        $reportAddress4->setCreatedAt(new \DateTimeImmutable('2024-11-15 16:45:00'));

        $manager->persist($reportAddress1);
        $manager->persist($reportAddress2);
        $manager->persist($reportAddress3);
        $manager->persist($reportAddress4);
        $manager->flush();

        $this->addReference('reportAddressTypical', $reportAddress1);
        $this->addReference('reportAddressNumberedRoad', $reportAddress2);
        $this->addReference('reportAddressNamedRoad', $reportAddress3);
        $this->addReference('reportAddressContacted', $reportAddress4);
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
