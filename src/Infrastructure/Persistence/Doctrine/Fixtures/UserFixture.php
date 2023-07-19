<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user1 = (new User('0b507871-8b5e-4575-b297-a630310fc06e'))
            ->setFullName('Mathieu MARCHOIS')
            ->setEmail('mathieu.marchois@beta.gouv.fr')
            ->setPassword('password');

        $user2 = (new User('d47badd9-989e-472b-a80e-9df642e93880'))
            ->setFullName('Florimond MANCA')
            ->setEmail('florimond.manca@beta.gouv.fr')
            ->setPassword('password');

        $manager->persist($user1);
        $manager->persist($user2);
        $manager->flush();

        $this->addReference('user1', $user1);
        $this->addReference('user2', $user2);
    }
}
