<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AddRegulationControllerTest extends WebTestCase
{
    public function addProvider(): array
    {
        return [
            ["includeOptional" => true],
            ["includeOptional" => false],
        ];
    }

    /**
     * @dataProvider addProvider
     */
    public function testAdd(bool $includeOptional): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/creer-une-restriction-de-circulation');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Créer une restriction de circulation', $crawler->filter('h1')->text());

        $saveButton = $crawler->selectButton("Enregistrer");
        $form = $saveButton->form();
        $form["regulation_order[description]"] = "Interdiction de circuler dans Paris";
        $form["regulation_order[issuingAuthority]"] = "Ville de Paris";
        $form["regulation_order[startPeriod]"] = "2022-12-07";

        if ($includeOptional) {
            $form["regulation_order[endPeriod]"] = "2022-12-17";
            $form["regulation_order[maxWeight]"] = "3.5";
            $form["regulation_order[maxHeight]"] = "2.80";
            $form["regulation_order[maxWidth]"] = "2";
            $form["regulation_order[maxLength]"] = "9";
        }

        $client->submit($form);
        $this->assertResponseRedirects("/", 302);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(3, $crawler->filter("tbody > tr")->count());
    }

    public function testInvalidMissingRequiredFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/creer-une-restriction-de-circulation');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton("Enregistrer");
        $form = $saveButton->form();

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(3, $crawler->filter('[id^="regulation_order_"][id$="_error"]'));
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#regulation_order_description_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#regulation_order_issuingAuthority_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#regulation_order_startPeriod_error')->text());
    }

    public function testInvalidPeriod(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/creer-une-restriction-de-circulation');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton("Enregistrer");
        $form = $saveButton->form();
        $form["regulation_order[description]"] = "Description";
        $form["regulation_order[issuingAuthority]"] = "Autorité compétente";
        $form["regulation_order[startPeriod]"] = "mauvais format";
        $form["regulation_order[endPeriod]"] = "mauvais format";

        $crawler = $client->submit($form);
        $this->assertCount(2, $crawler->filter('[id^="regulation_order_"][id$="_error"]'));
        $this->assertSame("Veuillez entrer une date valide.", $crawler->filter('#regulation_order_startPeriod_error')->text());
        $this->assertSame("Veuillez entrer une date valide.", $crawler->filter('#regulation_order_endPeriod_error')->text());

        $form["regulation_order[startPeriod]"] = "2022-12-07";
        $form["regulation_order[endPeriod]"] = "2022-12-05";

        $crawler = $client->submit($form);
        $this->assertCount(1, $crawler->filter('[id^="regulation_order_"][id$="_error"]'));
        $this->assertCount(1, $crawler->filter('#regulation_order_endPeriod_error')); // Cette valeur doit être supérieure à 7 déc. 2022.
    }

    public function testInvalidVehicleCharacteristics(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/creer-une-restriction-de-circulation');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton("Enregistrer");
        $form = $saveButton->form();
        $form["regulation_order[description]"] = "Description";
        $form["regulation_order[issuingAuthority]"] = "Autorité compétente";
        $form["regulation_order[startPeriod]"] = "2022-12-07";
        $form["regulation_order[maxWeight]"] = "not a number";
        $form["regulation_order[maxHeight]"] = "not a number";
        $form["regulation_order[maxWidth]"] = "not a number";
        $form["regulation_order[maxLength]"] = "not a number";

        $crawler = $client->submit($form);
        $this->assertCount(4, $crawler->filter('[id^="regulation_order_"][id$="_error"]'));
        $this->assertSame("Veuillez saisir un nombre.", $crawler->filter('#regulation_order_maxWeight_error')->text());
        $this->assertSame("Veuillez saisir un nombre.", $crawler->filter('#regulation_order_maxHeight_error')->text());
        $this->assertSame("Veuillez saisir un nombre.", $crawler->filter('#regulation_order_maxWidth_error')->text());
        $this->assertSame("Veuillez saisir un nombre.", $crawler->filter('#regulation_order_maxLength_error')->text());

        $form["regulation_order[maxWeight]"] = "-1";
        $form["regulation_order[maxHeight]"] = "-12";
        $form["regulation_order[maxWidth]"] = "-6";
        $form["regulation_order[maxLength]"] = "-1.23";

        $crawler = $client->submit($form);
        $this->assertCount(4, $crawler->filter('[id^="regulation_order_"][id$="_error"]'));
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#regulation_order_maxWeight_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#regulation_order_maxHeight_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#regulation_order_maxWidth_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#regulation_order_maxLength_error')->text());

        $form["regulation_order[maxWeight]"] = "0";
        $form["regulation_order[maxHeight]"] = "0";
        $form["regulation_order[maxWidth]"] = "0";
        $form["regulation_order[maxLength]"] = "0";

        $crawler = $client->submit($form);
        $this->assertCount(4, $crawler->filter('[id^="regulation_order_"][id$="_error"]'));
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#regulation_order_maxWeight_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#regulation_order_maxHeight_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#regulation_order_maxWidth_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#regulation_order_maxLength_error')->text());
    }
}
