<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class LandingDigitalServicesControllerTest extends AbstractWebTestCase
{
    public function testPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/digitalservices');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertMetaTitle('La réglementation de circulation accessible, à jour et standardisée - DiaLog', $crawler);

        $this->assertPageStructure([
            ['h1', 'La réglementation de circulation accessible, à jour et standardisée'],
            ['a', 'Accéder aux données', ['href' => 'https://www.data.gouv.fr/fr/datasets/64947a4af5faf2f1f9eee299/']],
            ['h2', 'Comment ça marche ?'],
            ['h2', 'Des données qualitatives'],
            ['a', 'point d’accès national aux données de transport.', ['href' => 'https://www.enable-javascript.com/fr/']],
            ['a', 'DATEX II', ['href' => 'https://www.datex2.eu/']],
            ['a', 'Contactez-nous', ['href' => 'mailto:dialog@beta.gouv.fr']],
            ['h2', 'Pourquoi numériser la réglementation ?'],
            ['a', 'Loi Climat et Résilience', ['href' => 'https://www.ecologie.gouv.fr/loi-climat-resilience']],
            ['a', 'Contactez-nous', ['href' => 'mailto:dialog@beta.gouv.fr']],
            ['h2', 'Un déploiement continu'],
            ['h3', 'Expérimentation'],
            ['h3', 'Déploiement en France'],
            ['h3', 'Déploiement en Europe'],
        ], $crawler);
    }
}
