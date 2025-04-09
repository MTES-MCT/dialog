<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\User\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderTemplateFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $globalRegulationOrderTemplate = new RegulationOrderTemplate(
            uuid: 'ba023736-35f6-49f4-a118-dc94f90ef42e',
            name: 'Restriction de vitesse sur route nationale',
            title: 'Arrete permanent n°[numero_arrete]',
            visaContent: 'VU ...',
            consideringContent: 'CONSIDERANT ...',
            articleContent: 'ARTICLES ...',
            createdAt: new \DateTime('2023-01-01'),
        );

        $regulationOrderTemplate = new RegulationOrderTemplate(
            uuid: '54eacea0-e1e0-4823-828d-3eae72b76da8',
            name: 'Réglementation de vitesse en agglomération',
            title: 'Arrete temporaire n°[numero_arrete]',
            visaContent: 'VU ...',
            consideringContent: 'CONSIDERANT ...',
            articleContent: 'ARTICLES ...',
            createdAt: new \DateTime('2025-04-08'),
            organization: $this->getReference('seineSaintDenisOrg', Organization::class),
        );

        $manager->persist($globalRegulationOrderTemplate);
        $manager->persist($regulationOrderTemplate);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixture::class,
        ];
    }
}
