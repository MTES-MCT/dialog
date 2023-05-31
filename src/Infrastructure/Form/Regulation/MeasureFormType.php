<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('periods', CollectionType::class, [
                'entry_type' => PeriodFormType::class,
                'entry_options' => ['label' => false],
                'prototype_name' => '__period_name__',
                'label' => 'regulation.period_list',
                'help' => 'regulation.period_list.help',
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])
            ->add(
                'allVehicles',
                ChoiceType::class,
                options: [
                    'choices' => [
                        'regulation.measure.all_vehicles.yes' => 'yes',
                        'regulation.measure.all_vehicles.no' => 'no',
                    ],
                    'label' => 'regulation.measure.all_vehicles',
                    'help' => 'regulation.measure.all_vehicles.help',
                    'expanded' => true,
                    'multiple' => false,
                ],
            )
            ->add(
                'restrictedVehicleTypes',
                ChoiceType::class,
                options: $this->getRestrictedVehicleTypesOptions(),
            )
            ->add(
                'otherRestrictedVehicleTypeText',
                TextType::class,
                options: [
                    'label' => 'regulation.measure.other_restricted_vehicle_type_text',
                    'help' => 'regulation.measure.other_restricted_vehicle_type_text.help',
                ],
            )
            ->add(
                'exemptedVehicleTypes',
                ChoiceType::class,
                options: $this->getExemptedVehicleTypesOptions(),
            )
            ->add(
                'otherExemptedVehicleTypeText',
                TextType::class,
                options: [
                    'label' => 'regulation.measure.other_exempted_vehicle_type_text',
                    'help' => 'regulation.measure.other_exempted_vehicle_type_text.help',
                ],
            )
        ;

        $builder->get('allVehicles')
            ->addModelTransformer(
                new CallbackTransformer(
                    transform: function (?bool $property): string {
                        return $property === null ? '' : ($property ? 'yes' : 'no');
                    },
                    reverseTransform: function (?string $property): ?bool {
                        return $property === null ? null : ($property === 'yes' ? true : false);
                    },
                ),
            );
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

    private function getRestrictedVehicleTypesOptions(): array
    {
        $choices = [];

        foreach (VehicleTypeEnum::forRestriction() as $case) {
            $choices[sprintf('regulation.measure.vehicle_types.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.measure.restricted_vehicle_types',
            'help' => 'regulation.measure.restricted_vehicle_types.help',
            'expanded' => true,
            'multiple' => true,
        ];
    }

    private function getExemptedVehicleTypesOptions(): array
    {
        $choices = [];

        foreach (VehicleTypeEnum::forExemption() as $case) {
            $choices[sprintf('regulation.measure.vehicle_types.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.measure.exempted_vehicle_types',
            'expanded' => true,
            'multiple' => true,
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveMeasureCommand::class,
        ]);
    }
}
