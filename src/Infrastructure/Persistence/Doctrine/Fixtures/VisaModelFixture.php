<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Visa\VisaModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class VisaModelFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $genericVisa = new VisaModel(
            '7eca6579-c07e-4e8e-8f10-fda610d7ee73',
            'Réglementation de vitesse en agglomération',
            ['Vu que 1', 'Vu que 2'],
            'Limitation de vitesse dans toute la commune',
            null,
        );

        $visa1 = new VisaModel(
            '65c12316-e210-445d-9169-0298b13b3b30',
            'Interdication de circulation',
            ['Vu que 3'],
            'Interdiction pour tous les véhicules',
            $this->getReference('mainOrg'),
        );

        $manager->persist($genericVisa);
        $manager->persist($visa1);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixture::class,
        ];
    }
}
