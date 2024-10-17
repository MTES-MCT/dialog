<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\User\Specification\DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SaveRegulationGeneralInfoCommandConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier $doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier,
    ) {
    }

    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveRegulationGeneralInfoCommand) {
            throw new UnexpectedValueException($command, SaveRegulationGeneralInfoCommand::class);
        }

        // Checking the unicity of an regulation order identifier in an organization
        $regulationOrder = $command->regulationOrderRecord?->getRegulationOrder();
        $hasIdentifierChanged = $regulationOrder?->getIdentifier() !== $command->identifier;
        $hasOrganizationChanged = $command->regulationOrderRecord?->getOrganization() !== $command->organization;

        if ($command->identifier && ($hasIdentifierChanged || $hasOrganizationChanged)) {
            if ($this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier
                ->isSatisfiedBy($command->identifier, $command->organization)) {
                $this->context->buildViolation('regulation.general_info.error.identifier')
                    ->setParameter('%identifier%', $command->identifier)
                    ->atPath('identifier')
                    ->addViolation();
            }
        }
    }
}
