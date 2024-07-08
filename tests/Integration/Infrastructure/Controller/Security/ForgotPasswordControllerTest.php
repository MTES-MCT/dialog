<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Security;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ForgotPasswordControllerTest extends AbstractWebTestCase
{
    public function testPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/mot-de-passe-oublie');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mot de passe oublié', $crawler->filter('h1')->text());
        $this->assertMetaTitle('Mot de passe oublié - DiaLog', $crawler);
        $this->assertSame('Pour toute demande de réinitialisation de mot de passe, veuillez contacter notre équipe à l\'adresse dialog@beta.gouv.fr en précisant l\'adresse e-mail du compte que vous souhaitez réinitialiser.', $crawler->filter('p[data-testid="forgot_password_instructions"]')->text());
    }
}
