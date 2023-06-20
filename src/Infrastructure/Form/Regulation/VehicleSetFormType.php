<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
