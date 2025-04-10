<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Organization\MailingList\MailingList;
use App\Domain\User\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class MailingListFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $mailingList = new MailingList(
            uuid: '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            name: 'Karine Marchand',
            email: 'email@mairie.gouv.fr',
            organization: $this->getReference('seineSaintDenisOrg', Organization::class),
            role: 'Mairie',
        );

        $manager->persist($mailingList);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixture::class,
        ];
    }
}
