<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Location\NumberedRoad;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SaveLocationCommandConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveLocationCommand) {
            throw new UnexpectedValueException($command, SaveLocationCommand::class);
        }

        // Pourquoi faire tout ce qui suit ?
        // Les routes départementales et nationales ont les mêmes champs et les même contraintes
        // Mais on ne veut appliquer les contraintes "NotBlank" que sur le sous-formulaire du type sélectionné,
        // par exemple uniquement sur les champs du sous-formulaire de route départementale si l'utilisateur a choisi Type = Départementale.
        // Or on ne peut pas imbriquer <When> et <Valid>, ni accéder au parent depuis une commande de validation
        // Par conséquent, on doit coder l'implémentation conditionnelle des NotBlank en PHP.

        foreach (['departmentalRoad', 'nationalRoad'] as $roadType) {
            if ($command->roadType !== $roadType) {
                continue;
            }

            // '$command->$roadType' is a 'variable variable' syntax => https://www.php.net/manual/en/language.variables.variable.php
            if (!$command->$roadType->administrator) {
                $this->context->buildViolation('common.error.not_blank')
                    ->atPath("$roadType.administrator")
                    ->addViolation();
            }

            if (!$command->$roadType->roadNumber) {
                $this->context->buildViolation('common.error.not_blank')
                    ->atPath("$roadType.roadNumber")
                    ->addViolation();
            }

            if (NumberedRoad::isPointNumberEmpty($command->$roadType->fromPointNumberWithDepartmentCode)) {
                $this->context->buildViolation('regulation.location.pointNumber.error.blank')
                    ->atPath("$roadType.fromPointNumber")
                    ->addViolation();
            }

            if (NumberedRoad::isPointNumberEmpty($command->$roadType->toPointNumberWithDepartmentCode)) {
                $this->context->buildViolation('regulation.location.pointNumber.error.blank')
                    ->atPath("$roadType.toPointNumber")
                    ->addViolation();
            }
        }
    }
}
