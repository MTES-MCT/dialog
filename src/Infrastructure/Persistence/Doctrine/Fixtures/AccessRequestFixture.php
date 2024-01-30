<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\AccessRequest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AccessRequestFixture extends Fixture
{
    public const UUID = '970f851a-566c-4d7c-89bb-f114efdc5f5e';

    public function load(ObjectManager $manager): void
    {
        $accessRequest = new AccessRequest(
            self::UUID,
            'Mathieu Marchois',
            'mathieu@fairness.coop',
            'Fairness',
            'password1',
            true,
            '82050375300015',
            'Cette application m\'interesse',
        );

        $manager->persist($accessRequest);
        $manager->flush();
    }
}
