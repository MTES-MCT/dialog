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
                'displayFutureRegulations',
                CheckboxType::class,
                options: [
                    'label' => 'map.form.displayFutureRegulations',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add(
                'displayPastRegulations',
                CheckboxType::class,
                options: [
                    'label' => 'map.form.displayPastRegulations',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add('save', SubmitType::class)
        ;
    }

    private function getCategoryOptions(): array
    {
        $choices = [
            'map.filter.permanents' => 'permanents_only',
            'map.filter.temporaries' => 'temporaries_only',
            'map.filter.all' => 'permanents_and_temporaries',
        ];

        return [
            'choices' => $choices,
            'label' => false,
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'data' => 'permanents_and_temporaries', // default value for the radio buttons
        ];
    }
}
