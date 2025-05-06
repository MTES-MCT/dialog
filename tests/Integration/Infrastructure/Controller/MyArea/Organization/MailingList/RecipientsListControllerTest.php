<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\MailingList;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class RecipientsListControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Liste de diffusion', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Liste de diffusion - DiaLog', $crawler);

        $rows = $crawler->filter('[data-testid="recipient-list"]');
        $tr0 = $rows->filter('tr')->eq(0)->filter('td');
        $this->assertCount(1, $rows->filter('tr'));

        $this->assertSame('Karine Marchand', $tr0->eq(0)->text());
        $this->assertSame('email@mairie.gouv.fr', $tr0->eq(1)->text());
        $this->assertSame('Mairie', $tr0->eq(2)->text());
        /* $action = $crawler->filter('[data-testid="update-mailing-list"]');
        $action->filter('a');
dd($action->filter('a'));
        $this->assertSame('Modifier', $tr0->eq(3)->attr('button')); */
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/recipients');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
