<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Steps;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Step5ControllerTest extends WebTestCase
{
    public function testSave(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Étape 5 sur 5 Récapitulatif', $crawler->filter('h2')->text());

        $step1 = $crawler->filter('div.for-what');
        $step2 = $crawler->filter('div.where');
        $step3 = $crawler->filter('div.when');
        $step4 = $crawler->filter('div.vehicles');

        // Step 1
        $this->assertSame('Description 1', $step1->filter('li')->eq(0)->text());
        $this->assertSame('Circulation interdite', $step1->filter('li')->eq(1)->text());
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5', $step1->filter('a')->link()->getUri());

        // Step 2
        $this->assertSame('Ville : 44260 Savenay', $step2->filter('li')->eq(0)->text());
        $this->assertSame('Rue : du 15 au 37bis, Route du Grand Brossais', $step2->filter('li')->eq(1)->text());
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/2', $step2->filter('a')->link()->getUri());

        // Step 3
        $this->assertSame('du 08/12/2022 au 18/12/2022', $step3->filter('li')->eq(0)->text());
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/3', $step3->filter('a')->link()->getUri());

        // Step 4
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/4', $step4->filter('a')->link()->getUri());
        $this->assertCount(0, $step4->filter('li'));

        // Status action
        $draftInput = $crawler->filter('input[id="status-draft"]')->first();
        $draftLabel = $draftInput->siblings()->filter('[for="status-draft"]')->first();
        $this->assertStringStartsWith('Sauvegarder le brouillon', $draftLabel->text());
        $publishedInput = $crawler->filter('input[id="status-published"]')->first();
        $publishedLabel = $publishedInput->siblings()->filter('[for="status-published"]')->first();
        $this->assertStringStartsWith('Valider la réglementation', $publishedLabel->text());

        $client->clickLink('Sauvegarder');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_list');
    }

    public function testPrevious(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Précédent');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_4', ['uuid' => 'e413a47e-5928-4353-a8b2-8b7dda27f9a5']);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/c1beed9a-6ec1-417a-abfd-0b5bd245616b/5');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/aaaaaaaa/5');

        $this->assertResponseStatusCodeSame(400);
    }


    public function testUxEnhancements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');
        $this->assertResponseStatusCodeSame(200);

        $backLink = $crawler->selectLink('Précédent');
        $this->assertNotNull($backLink->closest('turbo-frame[id="step-content"][data-turbo-action="advance"][autoscroll]'));
    }
}
