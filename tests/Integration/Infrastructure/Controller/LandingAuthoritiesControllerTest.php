<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class LandingAuthoritiesControllerTest extends AbstractWebTestCase
{
    public function testPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/collectivites');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertMetaTitle('Collectivités - DiaLog', $crawler);

        $this->assertPageStructure([
            ['h1', 'Numériser votre réglementation de circulation routière avec DiaLog'],
            ['a', 'Créer un compte pour ma commune', ['href' => '/access-request']],
            ['h2', 'Facilitez la vie de votre service voirie'],
            ['h3', 'Gagnez du temps'],
            ['h3', 'Simplifiez vos échanges'],
            ['h2', 'Comment ça marche ?'],
            ['h3', 'Créer un compte DiaLog'],
            ['a', 'Créer un compte', ['href' => '/access-request']],
            ['h3', 'Renseigner mes arrêtés'],
            ['h3', 'Diffuser mes données'],
            ['h2', 'Rejoignez les communes pilotes'],
            ['h3', "Participez à l'expérimentation"],
            ['a', 'Créer un compte', ['href' => '/access-request']],
        ], $crawler);
    }
}
