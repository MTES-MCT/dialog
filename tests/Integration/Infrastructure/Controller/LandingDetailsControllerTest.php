<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class LandingDetailsControllerTest extends AbstractWebTestCase
{
    public function testLanding(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/details');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertMetaTitle('Saisir ou intégrer des arrêtés - DiaLog', $crawler);

        $this->assertSkipLinks(
            [
                ['Contenu', '#content'],
                ['Menu', '#header-navigation'],
                ['Pied de page', '#footer'],
            ],
            $crawler,
        );

        $this->assertPageStructure(
            [
                ['button', 'Voir le fil d\'Ariane'],
                ['a', 'Accueil', ['href' => '/']],
                ['a', 'Saisir ou intégrer des arrêtés', ['href' => null]],
                ['h1', 'Saisir des arrêtés ou intégrer vos données dans DiaLog'],
                ['h2', 'Saisir un arrêté dans DiaLog'],
                ['h3', 'Créer un compte DiaLog'],
                ['a', 'Créer un compte', ['href' => '/register']],
                ['h3', 'Renseigner mes arrêtés'],
                ['a', 'Voir la vidéo de création des arrêtés', ['href' => 'https://tube.numerique.gouv.fr/w/ry5FweKbZuU2ddNpMm6LNU']],
                ['h3', 'Diffuser mes données'],
                ['h2', 'Importer des arrêtés via l\'API DiaLog'],
                ['a', 'Voir la documentation technique de l’API (Swagger)', ['href' => 'https://dialog.beta.gouv.fr/api/doc']],
                ['a', 'Voir le guide d’utilisation de l’API (Github)', ['href' => 'https://github.com/MTES-MCT/dialog/blob/main/docs/public/api.md']],
                ['h2', 'Récupérer vos données depuis votre logiciel de saisie des arrêtés'],
                ['a', 'Nous contacter', ['href' => '/contact']],
                ['h2', 'Pour en savoir plus'],
                ['h3', 'Aide en ligne'],
                ['a', 'Aide en ligne', ['href' => 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr']],
                ['h2', 'Des questions ?'],
                ['a', 'Nous contacter', ['href' => '/contact']],
            ],
            $crawler,
        );
    }
}
