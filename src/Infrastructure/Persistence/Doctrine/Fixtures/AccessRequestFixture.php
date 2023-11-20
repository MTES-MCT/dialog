<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\AccessRequest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AccessRequestFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $accessRequest = new AccessRequest(
            '970f851a-566c-4d7c-89bb-f114efdc5f5e',
            'Mathieu Marchois',
            'mathieu@fairness.coop',
            'Fairness',
            '82050375300015',
            'password1',
            true,
            'Cette application m\'interesse',
        );

        $manager->persist($accessRequest);
        $manager->flush();
    }
}
