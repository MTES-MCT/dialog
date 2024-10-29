<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Organization\VisaModel\VisaModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class VisaModelFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $genericVisa = (new VisaModel('7eca6579-c07e-4e8e-8f10-fda610d7ee73'))
            ->setName('Réglementation de vitesse en agglomération')
            ->setDescription('Limitation de vitesse dans toute la commune de l\'agglomération truc muche')
            ->setVisas(['vu que 1', 'vu que 2']);

        $visa1 = (new VisaModel('65c12316-e210-445d-9169-0298b13b3b30'))
            ->setName('Interdiction de circulation')
            ->setDescription('Interdiction pour tous les véhicules')
            ->setOrganization($this->getReference('mainOrg'))
            ->setVisas(['vu que 3']);

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
