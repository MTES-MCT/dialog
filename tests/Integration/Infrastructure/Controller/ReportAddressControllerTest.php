<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use Symfony\UX\Turbo\TurboBundle;

final class ReportAddressControllerTest extends AbstractWebTestCase
{
    public function testGetForm(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/report-address');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame(TurboBundle::STREAM_FORMAT);
        $this->assertSecurityHeaders();

        $streams = $crawler->filter('turbo-stream');
        $this->assertCount(1, $streams);
        $this->assertSame('update', $streams->eq(0)->attr('action'));
        $this->assertSame('create-report-address-form-frame', $streams->eq(0)->attr('target'));

        $template = $streams->eq(0)->filter('template');
        $this->assertSame('Localisation du problème Saisissez la localisation du problème, par exemple Route nationale N176', $template->filter('label[for*="location"]')->text());
        $this->assertSame('Description du problème Décrivez ici le problème, par exemple le nom de la rue non trouvée ou le numéro de PR sur une route.', $template->filter('label[for*="content"]')->text());
    }

    public function testGetFormWithQueryParametersForNumberedRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/report-address?administrator=Route%20d%C3%A9partementale&roadNumber=D12&frameId=custom-frame-id');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame(TurboBundle::STREAM_FORMAT);

        $streams = $crawler->filter('turbo-stream');
        $this->assertSame('custom-frame-id', $streams->eq(0)->attr('target'));

        $template = $streams->eq(0)->filter('template');
        $locationInput = $template->filter('input[name*="location"]');
        $this->assertSame('Route départementale - D12', $locationInput->attr('value'));
    }

    public function testGetFormWithQueryParametersForNamedRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/report-address?cityLabel=Paris&roadName=Rue%20de%20la%20Paix');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame(TurboBundle::STREAM_FORMAT);

        $template = $crawler->filter('turbo-stream template');
        $locationInput = $template->filter('input[name*="location"]');
        $this->assertSame('Paris - Rue de la Paix', $locationInput->attr('value'));
    }

    public function testGetFormWithRoadBanId(): void
    {
        $client = $this->login();
        $queryParams = [
            'cityLabel' => 'Paris',
            'roadName' => 'Rue de la Paix',
            'roadBanId' => '42059_0815',
        ];
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/report-address', $queryParams);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame(TurboBundle::STREAM_FORMAT);

        $template = $crawler->filter('turbo-stream template');
        $locationInput = $template->filter('input[name*="location"]');
        $this->assertSame('Paris - Rue de la Paix', $locationInput->attr('value'));
    }

    public function testSubmitValidForm(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/report-address?administrator=Route%20d%C3%A9partementale&roadNumber=D12&frameId=custom-frame-id');

        $this->assertResponseStatusCodeSame(200);

        $template = $crawler->filter('turbo-stream template');
        $saveButton = $template->selectButton('Signaler');
        $form = $saveButton->form();
        $form['report_address_form[content]'] = 'Il y a un problème avec cette adresse, la signalisation est absente.';

        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame(TurboBundle::STREAM_FORMAT);

        $streams = $crawler->filter('turbo-stream');
        $this->assertCount(1, $streams);
        $this->assertSame('update', $streams->eq(0)->attr('action'));

        $template = $streams->eq(0)->filter('template');
        $script = $template->filter('script');
        $this->assertCount(1, $script);
        $this->assertStringContainsString('/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL, $script->text());
    }

    public function testSubmitValidFormWithRoadBanId(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/report-address?cityLabel=Paris&roadName=Rue%20de%20la%20Paix&roadBanId=42059_0815');

        $this->assertResponseStatusCodeSame(200);

        $template = $crawler->filter('turbo-stream template');
        $saveButton = $template->selectButton('Signaler');
        $form = $saveButton->form();
        $form['report_address_form[content]'] = 'Il y a un problème avec cette adresse, le roadBanId n\'est pas reconnu.';

        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame(TurboBundle::STREAM_FORMAT);
    }

    public function testEmptyData(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/report-address');

        $this->assertResponseStatusCodeSame(200);

        $template = $crawler->filter('turbo-stream template');
        $saveButton = $template->selectButton('Signaler');
        $form = $saveButton->form();
        $form['report_address_form[content]'] = '';
        $form['report_address_form[location]'] = '';

        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseFormatSame(TurboBundle::STREAM_FORMAT);

        $template = $crawler->filter('turbo-stream template');
        $this->assertSame('Cette valeur ne doit pas être vide.', $template->filter('#report_address_form_content_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $template->filter('#report_address_form_location_error')->text());
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/report-address');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testWithInvalidUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/invalid-uuid/report-address');

        $this->assertResponseStatusCodeSame(404);
    }
}
