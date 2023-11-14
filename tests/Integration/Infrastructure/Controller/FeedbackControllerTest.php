<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class FeedbackControllerTest extends AbstractWebTestCase
{
    public function testFeedback(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/feedback');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Votre Avis', $crawler->filter('h1')->text());
        $this->assertMetaTitle('DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Envoyer');
        $form = $saveButton->form();
        $form['feedback_form[content]'] = 'Je souhaite signaler une amélioration possible';
        $form['feedback_form[consentToBeContacted]'] = '1';
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_feedback');
    }

    public function testEmptyData(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/feedback');

        $saveButton = $crawler->selectButton('Envoyer');
        $form = $saveButton->form();
        $form['feedback_form[content]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#feedback_form_content_error')->text());
    }
}
