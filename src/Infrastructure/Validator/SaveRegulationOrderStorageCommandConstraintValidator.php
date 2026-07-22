<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\SaveRegulationOrderStorageCommand;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SaveRegulationOrderStorageCommandConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveRegulationOrderStorageCommand) {
            throw new UnexpectedValueException($command, SaveRegulationOrderStorageCommand::class);
        }

        // On doit disposer d'un fichier (nouvel import ou fichier déjà stocké) ou d'une URL
        $hasFile = $command->file !== null || $command->path !== null;
        $hasUrl = $command->url !== null;

        if (!$hasFile && !$hasUrl) {
            // La violation est ajoutée sur les deux champs car le champ affiché dépend du choix (fichier / lien)
            $this->context->buildViolation('regulation.storage.error.file_or_url_required')
                ->atPath('file')
                ->addViolation();

            $this->context->buildViolation('regulation.storage.error.file_or_url_required')
                ->atPath('url')
                ->addViolation();
        }
    }
}
