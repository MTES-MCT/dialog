<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Specification\CanUseRawGeoJSON;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SaveLocationCommandConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private Security $security,
        private CanUseRawGeoJSON $canUseRawGeoJSON,
    ) {
    }

    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveLocationCommand) {
            throw new UnexpectedValueException($command, SaveLocationCommand::class);
        }

        if ($command->roadType === RoadTypeEnum::RAW_GEOJSON->value && !$this->canUseRawGeoJSON->isSatisfiedBy($this->security->getUser()?->getRoles())) {
            $this->context->buildViolation('regulation.location.error.road_type')
                ->atPath('roadType')
                ->addViolation();
        }
    }
}
