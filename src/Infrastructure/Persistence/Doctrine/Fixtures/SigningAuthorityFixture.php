<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Organization\SigningAuthority\SigningAuthority;
use App\Domain\User\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class SigningAuthorityFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $signatoryAuthority = new SigningAuthority(
            uuid: '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            name: 'Monsieur le maire de Savenay',
            role: 'Adjoint au maire',
            signatoryName: 'Monsieur X',
            organization: $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $manager->persist($signatoryAuthority);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixture::class,
        ];
    }
}
