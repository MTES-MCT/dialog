<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Domain\Regulation\Location\NumberedRoad;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Contrôles « champ requis » d'un sous-formulaire de route numérotée, partagés entre la
 * localisation (SaveLocationCommandConstraintValidator) et les exceptions « Ville entière »
 * (SaveWholeCityExceptionCommandConstraintValidator) : mêmes règles, mêmes messages.
 * Les NotBlank conditionnels ne peuvent pas s'exprimer en XML via <When> car les deux
 * sous-formulaires de route numérotée reçoivent le même roadType soumis.
 */
final class NumberedRoadRequiredFieldsValidator
{
    public static function validate(ExecutionContextInterface $context, ?SaveNumberedRoadCommand $command, string $basePath): void
    {
        if ($command === null) {
            $context->buildViolation('common.error.not_blank')
                ->atPath($basePath)
                ->addViolation();

            return;
        }

        // « 0 » est une valeur renseignée : pas de test de vérité PHP (empty('0') vaut true),
        // même piège que pour les PR (voir NumberedRoad::isPointNumberEmpty).
        if ($command->administrator === null || $command->administrator === '') {
            $context->buildViolation('common.error.not_blank')
                ->atPath("$basePath.administrator")
                ->addViolation();
        }

        if ($command->roadNumber === null || $command->roadNumber === '') {
            $context->buildViolation('common.error.not_blank')
                ->atPath("$basePath.roadNumber")
                ->addViolation();
        }

        if (NumberedRoad::isPointNumberEmpty($command->fromPointNumberWithDepartmentCode)) {
            $context->buildViolation('regulation.location.pointNumber.error.blank')
                ->atPath("$basePath.fromPointNumber")
                ->addViolation();
        }

        if (NumberedRoad::isPointNumberEmpty($command->toPointNumberWithDepartmentCode)) {
            $context->buildViolation('regulation.location.pointNumber.error.blank')
                ->atPath("$basePath.toPointNumber")
                ->addViolation();
        }
    }
}
