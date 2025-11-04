<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Location\NumberedRoad;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SaveLocationCommandConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveLocationCommand) {
            throw new UnexpectedValueException($command, SaveLocationCommand::class);
        }

        // Un seul type de localisation doit être présent
        $hasNamedStreet = $command->namedStreet?->roadName !== null;
        $hasDepartmentalRoad = $command->departmentalRoad?->roadNumber !== null;
        $hasNationalRoad = $command->nationalRoad?->roadNumber !== null;
        $hasRawGeoJSON = $command->rawGeoJSON?->label !== null;

        $filledCount = ($hasNamedStreet ? 1 : 0)
            + ($hasDepartmentalRoad ? 1 : 0)
            + ($hasNationalRoad ? 1 : 0)
            + ($hasRawGeoJSON ? 1 : 0);

        if ($filledCount !== 1) {
            $this->context->buildViolation($this->translator->trans('regulation.location.type.error.exclusive', [], 'messages'))
                ->addViolation();
        }

        // La section doit correspondre au roadType
        $expectedFieldByType = [
            'lane' => 'namedStreet',
            'departmentalRoad' => 'departmentalRoad',
            'nationalRoad' => 'nationalRoad',
            'rawGeoJSON' => 'rawGeoJSON',
        ];

        $roadType = $command->roadType;
        if (isset($expectedFieldByType[$roadType])) {
            $expectedField = $expectedFieldByType[$roadType];
            if ($command->$expectedField === null) {
                $this->context->buildViolation($this->translator->trans('regulation.location.type.error.mismatch', [], 'messages'))
                    ->atPath('roadType')
                    ->addViolation();

                return;
            }
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

            if ($command->$roadType === null) {
                $this->context->buildViolation('common.error.not_blank')
                    ->atPath($roadType)
                    ->addViolation();

                return;
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
