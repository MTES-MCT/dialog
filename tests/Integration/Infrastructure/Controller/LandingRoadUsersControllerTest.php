<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class LandingRoadUsersControllerTest extends AbstractWebTestCase
{
    public function testPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/usagers');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertMetaTitle('Usagers - DiaLog', $crawler);

        $this->assertPageStructure([
            ['h1', 'Vous êtes sur la bonne voie !'],
            ['h2', 'Comment ça marche ?'],
            ['h2', 'Un dispositif soutenu par l\'Etat'],
            ['a', 'loi Climat et Résilience', ['href' => 'https://www.vie-publique.fr/loi/278460-loi-22-aout-2021-climat-et-resilience-convention-citoyenne-climat']],
            ['h2', 'Pour en savoir plus'],
            ['h3', 'Comment sont gérées les routes en France ?'],
            ['h4', 'Voies communales'],
            ['h4', 'Voies départementales'],
            ['h4', 'Voies nationales'],
            ['a', 'acteurs routiers', ['href' => 'https://www.ecologie.gouv.fr/acteurs-route-en-france']],
        ], $crawler);
    }
}
