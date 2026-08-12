<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class FooterTest extends AbstractWebTestCase
{
    public function testFooterTopLinks(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);

        $categories = $crawler->filter('footer .fr-footer__top-cat')->each(fn ($node) => $node->text());
        $this->assertSame(['Liens du site', 'Ressources', 'Données', 'Contact et support'], $categories);

        $links = $crawler->filter('footer .fr-footer__top-link')->each(
            fn ($node) => [$node->text(), $node->attr('href')],
        );
        $this->assertSame([
            ['Accueil', '/'],
            ['Carte des restrictions', '/carte'],
            ['Liste des arrêtés', '/regulations'],
            ['Saisir des arrêtés ou intégrer vos données dans DiaLog', '/details'],
            ['Aide', 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr'],
            ['Nouveautés', 'https://fabrique-numerique.gitbook.io/doc.dialog.beta.gouv.fr/en-savoir-plus-sur-dialog/note-de-version'],
            ['Blog', '/blog/fr/'],
            ['Vidéos', 'https://tube.numerique.gouv.fr/c/dialog/videos'],
            ['Statistiques', '/stats'],
            ['Documentation API', '/api/doc'],
            ['Code source', 'https://github.com/MTES-MCT/dialog'],
            ['Donnez-nous votre avis', '/feedback'],
            ['Contactez-nous', '/contact'],
        ], $links);
    }

    public function testFooterBottomLinks(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);

        $links = $crawler->filter('footer .fr-footer__bottom-link')->each(
            fn ($node) => [$node->text(), $node->attr('href')],
        );
        $this->assertSame([
            ['Accessibilité : non conforme', '/accessibility'],
            ['Mentions légales', '/mentions-legales'],
            ['Politique de confidentialité', '/politique-de-confidentialite'],
            ['Conditions générales d’utilisation', '/conditions-generales-d-utilisation'],
            ['Écoconception', '/ecoconception'],
            ['Contact', '/contact'],
        ], $links);
    }
}
