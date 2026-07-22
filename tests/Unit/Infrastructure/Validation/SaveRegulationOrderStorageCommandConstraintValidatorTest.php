<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Validation;

use App\Application\Regulation\Command\SaveRegulationOrderStorageCommand;
use App\Domain\Regulation\RegulationOrder;
use App\Infrastructure\Validator\SaveRegulationOrderStorageCommandConstraint;
use App\Infrastructure\Validator\SaveRegulationOrderStorageCommandConstraintValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SaveRegulationOrderStorageCommandConstraintValidatorTest extends ConstraintValidatorTestCase
{
    private $constraintObj;
    private $regulationOrder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->constraintObj = new SaveRegulationOrderStorageCommandConstraint();
        $this->regulationOrder = $this->createMock(RegulationOrder::class);
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new SaveRegulationOrderStorageCommandConstraintValidator();
    }

    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate('not a command instance', $this->constraintObj);
    }

    public function testValidWithFile(): void
    {
        $command = new SaveRegulationOrderStorageCommand($this->regulationOrder, null);
        $command->file = $this->createMock(UploadedFile::class);

        $this->validator->validate($command, $this->constraintObj);
        $this->assertNoViolation();
    }

    public function testValidWithUrl(): void
    {
        $command = new SaveRegulationOrderStorageCommand($this->regulationOrder, null);
        $command->url = 'https://example.com/storage.pdf';

        $this->validator->validate($command, $this->constraintObj);
        $this->assertNoViolation();
    }

    public function testValidWithExistingStoredFile(): void
    {
        // Cas de l'édition : aucun nouveau fichier importé mais un fichier est déjà stocké
        $command = new SaveRegulationOrderStorageCommand($this->regulationOrder, null);
        $command->path = 'regulationOrder/496bd752-c217-4625-ba0c-7454dc218516/storageRegulationOrder.pdf';

        $this->validator->validate($command, $this->constraintObj);
        $this->assertNoViolation();
    }

    public function testViolationWhenEmpty(): void
    {
        $command = new SaveRegulationOrderStorageCommand($this->regulationOrder, null);

        $this->validator->validate($command, $this->constraintObj);

        $this->buildViolation('regulation.storage.error.file_or_url_required')
            ->atPath('property.path.file')
            ->buildNextViolation('regulation.storage.error.file_or_url_required')
            ->atPath('property.path.url')
            ->assertRaised();
    }
}
