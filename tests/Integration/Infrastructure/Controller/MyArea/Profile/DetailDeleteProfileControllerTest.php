<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Profile;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class DetailDeleteProfileControllerTest extends AbstractWebTestCase
{
    public function testDetailDeleteProfilePage(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/profile/delete/detail');

        $this->assertResponseStatusCodeSame(200);
        $this->assertMetaTitle('Mathieu FERNANDEZ - DiaLog', $crawler);
        $this->assertSame('Suppression du compte', $crawler->filter('h2')->text());
    }
}
