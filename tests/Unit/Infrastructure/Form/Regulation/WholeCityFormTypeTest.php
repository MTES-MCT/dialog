<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\Organization\Establishment\Establishment;
use App\Domain\User\Organization;
use App\Infrastructure\Form\Regulation\WholeCityFormType;
use Symfony\Component\Form\Test\TypeTestCase;

final class WholeCityFormTypeTest extends TypeTestCase
{
    private function createCommuneOrganization(bool $withEstablishment = true): Organization
    {
        $organization = (new Organization('f5c1cea8-a61d-43a1-9236-f6acc121392b'))
            ->setName('Kerlouan')
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'))
            ->setCode('29091')
            ->setCodeType(OrganizationCodeTypeEnum::INSEE->value);

        if ($withEstablishment) {
            $organization->setEstablishment(
                new Establishment(
                    uuid: '11b1c0bc-33bd-4a3a-a0bb-a6c191bfe83b',
                    address: '1 rue de la Mairie',
                    zipCode: '29890',
                    city: 'Kerlouan',
                    organization: $organization,
                ),
            );
        }

        return $organization;
    }

    public function testPrefillsCityForCommuneOrganization(): void
    {
        $form = $this->factory->create(WholeCityFormType::class, null, [
            'organization' => $this->createCommuneOrganization(),
        ]);

        $command = $form->getData();

        $this->assertInstanceOf(SaveWholeCityCommand::class, $command);
        $this->assertSame('29091', $command->cityCode);
        $this->assertSame('Kerlouan (29890)', $command->cityLabel);
    }

    public function testPrefillsCityLabelFallsBackToOrganizationName(): void
    {
        $form = $this->factory->create(WholeCityFormType::class, null, [
            'organization' => $this->createCommuneOrganization(withEstablishment: false),
        ]);

        $command = $form->getData();

        $this->assertInstanceOf(SaveWholeCityCommand::class, $command);
        $this->assertSame('29091', $command->cityCode);
        $this->assertSame('Kerlouan', $command->cityLabel);
    }

    public function testDoesNotPrefillForNonCommuneOrganization(): void
    {
        $organization = (new Organization('8f9164ed-dc0f-4c98-ac18-2f590a1cfd22'))
            ->setName('Département de Seine-Saint-Denis')
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'))
            ->setCode('93')
            ->setCodeType(OrganizationCodeTypeEnum::DEPARTMENT->value);

        $form = $this->factory->create(WholeCityFormType::class, null, [
            'organization' => $organization,
        ]);

        $this->assertNull($form->getData());
    }

    public function testDoesNotPrefillWithoutOrganization(): void
    {
        $form = $this->factory->create(WholeCityFormType::class);

        $this->assertNull($form->getData());
    }

    public function testDoesNotOverrideExistingCity(): void
    {
        $command = new SaveWholeCityCommand();
        $command->cityCode = '93070';
        $command->cityLabel = 'Saint-Ouen-sur-Seine (93400)';

        $form = $this->factory->create(WholeCityFormType::class, $command, [
            'organization' => $this->createCommuneOrganization(),
        ]);

        $result = $form->getData();

        $this->assertSame('93070', $result->cityCode);
        $this->assertSame('Saint-Ouen-sur-Seine (93400)', $result->cityLabel);
    }
}
