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

        // Libellés courts de la maquette (« A vers B »). L'export CSV garde les siens
        // (« Sens A vers B »), déjà publiés dans la colonne sens_circulation.
        foreach (DirectionEnum::cases() as $case) {
            $choices[\sprintf('regulation.location.direction.choice.%s', $case->value)] = $case->value;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'label' => 'regulation.location.direction',
            'help' => 'regulation.location.direction.help',
            // Cible du contrôleur one-way-direction : « Double sens » est retiré pour un sens unique
            'attr' => ['data-one-way-direction-target' => 'direction'],
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
