<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation\Measure;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Infrastructure\Form\Regulation\LocationFormType;
use App\Infrastructure\Form\Regulation\PeriodFormType;
use App\Infrastructure\Form\Regulation\VehicleSetFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MeasureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'type',
                ChoiceType::class,
                options: $this->getTypeOptions(),
            )
            ->add(
                'maxSpeed',
                NumberType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.measure.maxSpeed.title',
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add('vehicleSet', VehicleSetFormType::class)
            ->add('periods', CollectionType::class, [
                'entry_type' => PeriodFormType::class,
                'entry_options' => [
                    'label' => false,
                    'isPermanent' => $options['isPermanent'],
                ],
                'prototype_name' => '__period_name__',
                'label' => 'regulation.period_list',
                'help' => 'regulation.period_list.help',
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])
            ->add('locations', CollectionType::class, [
                'entry_type' => LocationFormType::class,
                'entry_options' => [
                    'label' => false,
                    'administrators' => $options['administrators'],
                ],
                'prototype_name' => '__location_name__',
                'label' => 'regulation.location_list',
                'help' => 'regulation.location_list.help',
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.validate',
                ],
            )
        ;
    }

    private function getTypeOptions(): array
    {
        $choices = [];

        foreach (MeasureTypeEnum::cases() as $case) {
            $choices[sprintf('regulation.measure.type.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => array_merge(
                ['regulation.measure.type.placeholder' => ''],
                $choices,
            ),
            'label' => 'regulation.measure.type',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveMeasureCommand::class,
            'administrators' => [],
            'isPermanent' => false,
            'validation_groups' => ['Default', 'html_form'],
        ]);
        $resolver->setAllowedTypes('administrators', 'array');
        $resolver->setAllowedTypes('isPermanent', 'boolean');
    }
}
