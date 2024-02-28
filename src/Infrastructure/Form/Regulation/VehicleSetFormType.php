<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class VehicleSetFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'allVehicles',
                ChoiceType::class,
                options: [
                    'choices' => [
                        'regulation.vehicle_set.all_vehicles.yes' => 'yes',
                        'regulation.vehicle_set.all_vehicles.no' => 'no',
                    ],
                    'label' => 'regulation.vehicle_set.all_vehicles',
                    'help' => 'regulation.vehicle_set.all_vehicles.help',
                    'expanded' => true,
                    'multiple' => false,
                ],
            )
            ->add(
                'restrictedTypes',
                ChoiceType::class,
                options: $this->getRestrictedTypesOptions(),
            )
            ->add(
                'heavyweightMaxWeight',
                NumberType::class,
                options: [
                    'label' => 'regulation.vehicle_set.heavyweightMaxWeight',
                    'help' => 'regulation.vehicle_set.heavyweightMaxWeight.help',
                    'required' => false,
                    'empty_data' => '3,5',
                ],
            )
            ->add(
                'maxWidth',
                NumberType::class,
                options: [
                    'label' => 'regulation.vehicle_set.maxWidth',
                    'required' => false,
                ],
            )
            ->add(
                'maxLength',
                NumberType::class,
                options: [
                    'label' => 'regulation.vehicle_set.maxLength',
                    'required' => false,
                ],
            )
            ->add(
                'maxHeight',
                NumberType::class,
                options: [
                    'label' => 'regulation.vehicle_set.maxHeight',
                    'required' => false,
                ],
            )
            ->add(
                'critairTypes',
                ChoiceType::class,
                options: $this->getCritairTypesOptions(),
            )
            ->add(
                'otherRestrictedTypeText',
                TextType::class,
                options: [
                    'label' => 'regulation.vehicle_set.other_restricted_type_text',
                    'help' => 'regulation.vehicle_set.other_restricted_type_text.help',
                ],
            )
            ->add(
                'exemptedTypes',
                ChoiceType::class,
                options: $this->getExemptedTypesOptions(),
            )
            ->add(
                'otherExemptedTypeText',
                TextType::class,
                options: [
                    'label' => 'regulation.vehicle_set.other_exempted_type_text',
                    'help' => 'regulation.vehicle_set.other_exempted_type_text.help',
                ],
            )
        ;

        $builder->get('critairTypes')
            ->addModelTransformer(
                new CallbackTransformer(
                    transform: fn ($critairTypes) => $critairTypes ?? [CritairEnum::CRITAIR_4->value, CritairEnum::CRITAIR_5->value],
                    reverseTransform: fn ($value) => $value,
                ),
            );

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

        $builder
            ->get('heavyweightMaxWeight')
            ->addModelTransformer(
                new CallbackTransformer(
                    transform: fn ($maxWeight) => $maxWeight ?? VehicleSet::DEFAULT_MAX_WEIGHT,
                    reverseTransform: fn ($value) => $value,
                ),
            );
    }

    private function getRestrictedTypesOptions(): array
    {
        $choices = [];

        foreach (VehicleTypeEnum::restrictedCases() as $case) {
            $choices[sprintf('regulation.vehicle_set.type.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.vehicle_set.restricted_types',
            'help' => 'regulation.vehicle_set.restricted_types.help',
            'expanded' => true,
            'multiple' => true,
        ];
    }

    private function getCritairTypesOptions(): array
    {
        $choices = [];

        foreach (CritairEnum::critairCases() as $value => $case) {
            $choices[$value] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.vehicle_set.critair',
            'help' => 'regulation.vehicle_set.critair.help',
            'expanded' => true,
            'multiple' => true,
            'required' => false,
        ];
    }

    private function getExemptedTypesOptions(): array
    {
        $choices = [];

        foreach (VehicleTypeEnum::exemptedCases() as $case) {
            $choices[sprintf('regulation.vehicle_set.type.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.vehicle_set.exempted_types',
            'expanded' => true,
            'multiple' => true,
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveVehicleSetCommand::class,
        ]);
    }
}
