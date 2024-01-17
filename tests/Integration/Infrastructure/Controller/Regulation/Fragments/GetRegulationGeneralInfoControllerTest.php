<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetRegulationGeneralInfoControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/general_info');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Description 3', $crawler->filter('h3')->text());
        $this->assertSame(OrganizationFixture::MAIN_ORG_NAME, $crawler->filter('li')->eq(0)->text());
        $this->assertSame('Réglementation permanente', $crawler->filter('li')->eq(1)->text());
        $this->assertSame('Description 3', $crawler->filter('li')->eq(2)->text());
        $this->assertSame('Depuis le 11/03/2023', $crawler->filter('li')->eq(3)->text());
        $editForm = $crawler->selectButton('Modifier')->form();
        $this->assertSame('http://localhost/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_PERMANENT, $editForm->getUri());
        $this->assertSame('GET', $editForm->getMethod());
    }

    public function testGetDescriptionTruncated(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_LONG_DESCRIPTION . '/general_info');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Description 5 that is very long and...', $crawler->filter('h3')->text());
    }

    public function testGetOtherCategoryTextDisplay(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_OTHER_CATEGORY . '/general_info');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Autre : Dérogation préfectorale', $crawler->filter('li')->eq(1)->text());
    }

    public function testGetPublished(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PUBLISHED . '/general_info');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(0, $crawler->filter('a')->count()); // Cannot edit
    }

    public function testRegulationDoesNotExist(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/general_info');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/general_info');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/general_info');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
