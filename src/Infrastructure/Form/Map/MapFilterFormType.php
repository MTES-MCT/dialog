<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Map;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class MapFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'category',
                ChoiceType::class,
                options: $this->getCategoryOptions(),
            )
            ->add(
                'display_future_regulations',
                CheckboxType::class,
                options: [
                    'label' => 'Arrêtés à venir',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add(
                'display_past_regulations',
                CheckboxType::class,
                options: [
                    'label' => 'Arrêtés passés',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add(
                'save',
                SubmitType::class,
            )
        ;
    }

    private function getCategoryOptions(): array
    {
        $choices = [
            'Arrêtés permanents' => 'permanents_only',
            'Arrêtés temporaires' => 'temporaries_only',
            'Tous les arrêtés' => 'permanents_and_temporaries',
        ];

        return [
            'choices' => $choices,
            'label' => false,
            'expanded' => true, // we want radio buttons
            'multiple' => false, // we want radio buttons
            'required' => true,
            'data' => 'permanents_and_temporaries', // default value for the radio buttons
        ];
    }
}
