<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Map;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class MapFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add(
            'displayMeasureTypeNoEntry',
            CheckboxType::class,
            options: [
                'label' => 'map.filter.type.noEntry',
                'value' => 'yes',
                'required' => false,
            ],
        )
        ->add(
            'displayMeasureTypeSpeedLimitation',
            CheckboxType::class,
            options: [
                'label' => 'map.filter.type.speedLimitation',
                'value' => 'yes',
                'required' => false,
            ],
        )
            ->add(
                'displayPermanentRegulations',
                CheckboxType::class,
                options: [
                    'label' => 'map.filter.permanents',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add(
                'displayTemporaryRegulations',
                CheckboxType::class,
                options: [
                    'label' => 'map.filter.temporaries',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add('save', SubmitType::class)
        ;
    }
}
