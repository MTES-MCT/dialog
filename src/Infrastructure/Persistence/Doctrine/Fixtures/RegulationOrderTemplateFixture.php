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
        $globalRegulationOrderTemplate = new RegulationOrderTemplate('ba023736-35f6-49f4-a118-dc94f90ef42e');
        $globalRegulationOrderTemplate
            ->setName('Restriction de vitesse sur route nationale')
            ->setTitle('Arrete permanent n°[numero_arrete]')
            ->setVisaContent('VU ...')
            ->setConsideringContent('CONSIDERANT ...')
            ->setArticleContent('ARTICLES ...')
            ->setCreatedAt(new \DateTimeImmutable('2023-01-01'));

        $regulationOrderTemplate = new RegulationOrderTemplate('54eacea0-e1e0-4823-828d-3eae72b76da8');
        $regulationOrderTemplate
            ->setName('Réglementation de vitesse en agglomération')
            ->setTitle('Arrete temporaire n°[numero_arrete]')
            ->setVisaContent('VU ...')
            ->setConsideringContent('CONSIDERANT ...')
            ->setArticleContent('ARTICLES ...')
            ->setCreatedAt(new \DateTimeImmutable('2025-04-08'))
            ->setOrganization($this->getReference('seineSaintDenisOrg', Organization::class));

        $manager->persist($globalRegulationOrderTemplate);
        $manager->persist($regulationOrderTemplate);
        $manager->flush();

        $this->addReference('regulationOrderTemplate', $regulationOrderTemplate);
        $this->addReference('globalRegulationOrderTemplate', $globalRegulationOrderTemplate);
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixture::class,
        ];
    }
}
