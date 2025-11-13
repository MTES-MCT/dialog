<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\News;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class NewsFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $newsNotice = new News('9cebe00d-04d8-48da-89b1-059f6b7bfe44');
        $newsNotice->setName('Dernières nouveautés');
        $newsNotice->setLinkTitle('Exporter mes arrêtés de circulation');
        $newsNotice->setLink('https://www.dialog.beta.gouv.fr/regulations/export');
        $newsNotice->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');
        $newsNotice->setCreatedAt(new \DateTimeImmutable('2025-02-12'));
        $manager->persist($newsNotice);
        $manager->flush();

        $this->addReference('newsNotice', $newsNotice);
    }
}
