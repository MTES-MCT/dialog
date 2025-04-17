<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\MailingList;

use App\Domain\Organization\MailingList\MailingList;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class MailingListTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = $this->createMock(Organization::class);

        $mailingList = new MailingList(
            'e21361a3-999f-45b7-91d1-12cacac3f15c',
            'Karine Marchand',
            'email@mairie.gouv.fr',
            $organization,
            'Maire',
        );

        $this->assertSame('e21361a3-999f-45b7-91d1-12cacac3f15c', $mailingList->getUuid());
        $this->assertSame('Karine Marchand', $mailingList->getName());
        $this->assertSame('email@mairie.gouv.fr', $mailingList->getEmail());
        $this->assertSame($organization, $mailingList->getOrganization());
        $this->assertSame('Maire', $mailingList->getRole());

        $mailingList->update(
            'Isabelle Truc',
            'isabelle@beta.gouv.fr',
            'Prefecture',
        );

        $this->assertSame('Isabelle Truc', $mailingList->getName());
        $this->assertSame('isabelle@beta.gouv.fr', $mailingList->getEmail());
        $this->assertSame('Prefecture', $mailingList->getRole());
    }
}
