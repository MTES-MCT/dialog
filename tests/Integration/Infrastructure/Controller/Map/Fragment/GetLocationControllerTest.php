<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map\Fragment;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetLocationControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/map/fragments/location/' . LocationFixture::UUID_PUBLISHED);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $li = $crawler->filter('ul > li');

        $this->assertSame('Circulation interdite', $li->eq(0)->text());
        $this->assertSame('Avenue de Fonneuve du n° 695 au n° 253 à Montauban (82000)', $li->eq(1)->text());
        $this->assertSame('pour les véhicules de plus de 3,5 tonnes, 12 mètres de long ou 2,4 mètres de haut, matières dangereuses, Crit\'Air 4 et Crit\'Air 5, sauf piétons, véhicules d\'urgence et convois exceptionnels', $li->eq(2)->text());
        $this->assertSame('tous les jours', $li->eq(3)->text());
        $this->assertSame('Du 10/03/2023 au 20/03/2023', $li->eq(4)->text());
        $this->assertSame('L\'arrêté est : passé', $li->eq(5)->text());
    }

    public function testLocationNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/map/fragments/location/' . LocationFixture::UUID_DOES_NOT_EXIST);

        $this->assertResponseStatusCodeSame(404);
    }
}
