<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddRegulationControllerTest extends AbstractWebTestCase
{
    public function testAdd(): void
    {
        $email = UserFixture::MAIN_ORG_USER_EMAIL;
        $client = $this->login($email);
        $crawler = $client->request('GET', '/regulations/add');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Nouvel arrêté', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Nouvel arrêté - DiaLog', $crawler);

        $this->assertSame('Brouillon', $crawler->filter('[data-testid="status-badge"]')->text());
        $this->assertSame('', $crawler->selectButton('Publier')->attr('disabled'));
        $this->assertSame('', $crawler->selectButton('Dupliquer')->attr('disabled'));
        $deleteLink = $crawler->selectLink('Supprimer');
        $this->assertSame('http://localhost/regulations', $deleteLink->link()->getUri());

        $this->assertStringContainsString('Les modèles de visas sont gérés', trim($crawler->filter('[data-testid="visa-info"]')->html()));

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();

        /** @var UserRepositoryInterface */
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $this->assertNull($userRepository->findOneByEmail($email)->getLastActiveAt());

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['general_info_form']['identifier'] = 'F022023';
        $values['general_info_form']['organization'] = OrganizationFixture::MAIN_ORG_ID;
        $values['general_info_form']['description'] = 'Interdiction de circuler dans Paris';
        $values['general_info_form']['category'] = RegulationOrderCategoryEnum::OTHER->value;
        $values['general_info_form']['otherCategoryText'] = 'Trou en formation';
        $values['general_info_form']['visaModelUuid'] = '7eca6579-c07e-4e8e-8f10-fda610d7ee73';
        $values['general_info_form']['additionalVisas'][0] = 'Vu 1';
        $values['general_info_form']['additionalVisas'][1] = 'Vu 2';
        $values['general_info_form']['additionalReasons'][0] = 'Motif 1';
        $values['general_info_form']['additionalReasons'][1] = 'Motif 2';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(303);
        $this->assertEquals(new \DateTimeImmutable('2023-06-09'), $userRepository->findOneByEmail($email)->getLastActiveAt());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulation_detail');
    }

    public function testEmptyData(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $form['general_info_form[category]'] = RegulationOrderCategoryEnum::OTHER->value;
        $form['general_info_form[otherCategoryText]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#general_info_form_otherCategoryText_error')->text());
    }

    public function testOtherCategoryTextMissing(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#general_info_form_identifier_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#general_info_form_description_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide. Cette valeur doit être l\'un des choix proposés.', $crawler->filter('#general_info_form_category_error')->text());
    }

    public function testAddWithAnAlreadyExistingIdentifier(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $identifier = RegulationOrderFixture::TYPICAL_IDENTIFIER;
        $form['general_info_form[identifier]'] = $identifier;
        $form['general_info_form[organization]'] = OrganizationFixture::MAIN_ORG_ID;
        $form['general_info_form[description]'] = 'Travaux';
        $form['general_info_form[category]'] = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(\sprintf('Un arrêté avec l\'identifiant "%s" existe déjà. Veuillez saisir un autre identifiant.', $identifier), $crawler->filter('#general_info_form_identifier_error')->text());
    }

    public function testCancel(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/add');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Annuler');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_list');
    }

    public function testFieldsTooLong(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $form['general_info_form[identifier]'] = str_repeat('a', 61);
        $form['general_info_form[description]'] = str_repeat('a', 256);
        $form['general_info_form[otherCategoryText]'] = str_repeat('a', 101);

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 60 caractères.', $crawler->filter('#general_info_form_identifier_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#general_info_form_description_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#general_info_form_otherCategoryText_error')->text());
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
