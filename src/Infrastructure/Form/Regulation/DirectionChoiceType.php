<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Domain\Regulation\Enum\DirectionEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Select « Sens concerné » (double sens, A vers B, B vers A), partagé entre les
 * voies nommées, les routes numérotées et le tracé libre.
 */
final class DirectionChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (DirectionEnum::cases() as $case) {
            $choices[\sprintf('regulation.location.direction.%s', $case->value)] = $case->value;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'label' => 'regulation.location.direction',
            'help' => 'regulation.location.direction.help',
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
