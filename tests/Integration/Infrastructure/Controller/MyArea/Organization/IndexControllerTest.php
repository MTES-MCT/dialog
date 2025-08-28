<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization;

use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class IndexControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/mon-espace/organizations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mes organisations', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Mes organisations - DiaLog', $crawler);

        $organizations = $crawler->filter('[data-testid="organization-list"]');
        $this->assertCount(2, $organizations->filter('[data-testid="organization-detail"]'));
        $this->assertSame('Complété Département de Seine-Saint-Denis Contributeur', $organizations->filter('[data-testid="organization-detail"]')->text());
        $this->assertCount(0, $crawler->filter('[data-testid="admin-link"]'));
    }

    public function testWithOrganizationsNotCompleted(): void
    {
        $client = $this->login('mathieu.marchois@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mes organisations', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Mes organisations - DiaLog', $crawler);

        $warningNotice = $crawler->filter('[data-testid="notice-warning"]');
        $this->assertSame('Complétez les informations de vos organisations Ajouter un logo à votre organisation, configurer des modèles d’arrêtés, créer une liste de diffusion, etc. Seul l’administrateur de l’organisation peut effectuer cette action. Merci de le contacter si ce n’est pas votre rôle. Voir mes organisations Masquer le message', $warningNotice->filter('[data-testid="organization-not-completed"]')->text());
        $this->assertSame('fr-notice fr-notice--warning', $warningNotice->attr('class'));
    }

    public function testWithOrganizationCompleted(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/regulations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertEmpty($crawler->filter('[data-testid="notice-warning"]'));
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
