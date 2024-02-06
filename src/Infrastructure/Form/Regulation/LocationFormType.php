<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Domain\Regulation\Enum\RoadTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LocationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['feature_road_type'] === true) {
            $builder
                ->add(
                    'roadType',
                    ChoiceType::class,
                    options: $this->getRoadTypeOptions(),
                );
        } else {
            $builder
                ->add(
                    'roadType',
                    HiddenType::class,
                    options: ['empty_data' => 'lane'],
                );
        }

        $builder
            ->add(
                'administrator',
                ChoiceType::class,
                options: $this->getAdministratorOptions($options['administrators']),
            )
            ->add(
                'roadNumber',
                TextType::class,
                options: [
                    'label' => 'regulation.location.roadNumber',
                    'required' => false, // Due to error "An invalid form control with name='x' is not focusable"
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add(
                'cityCode',
                HiddenType::class,
            )
            ->add(
                'cityLabel',
                TextType::class,
                options: [
                    'label' => 'regulation.location.city',
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add(
                'roadName',
                TextType::class,
                options: [
                    'label' => 'regulation.location.roadName',
                    'help' => 'regulation.location.roadName.help',
                    'required' => false,
                ],
            )
            ->add(
                'fromHouseNumber',
                TextType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.location.from_house_number',
                ],
            )
            ->add(
                'toHouseNumber',
                TextType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.location.to_house_number',
                ],
            )
            ->add('measures', CollectionType::class, [
                'entry_type' => MeasureFormType::class,
                'prototype_name' => '__measure_name__',
                'entry_options' => ['label' => false],
                'label' => 'regulation.measure_list',
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
            );
    }

    private function getRoadTypeOptions(): array
    {
        $choices = [];

        foreach (RoadTypeEnum::cases() as $case) {
            $choices[sprintf('regulation.location.road.type.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => array_merge(
                ['regulation.location.type.placeholder' => ''],
                $choices,
            ),
            'label' => 'regulation.location.type',
            'label_attr' => [
                'class' => 'required',
            ],
        ];
    }

    private function getAdministratorOptions(array $administrators): array
    {
        $choices = [];

        foreach ($administrators as $value) {
            $choices[$value] = $value;
        }

        return [
            'label' => 'regulation.location.administrator',
            'label_attr' => [
                'class' => 'required',
            ],
            'required' => false, // Due to error "An invalid form control with name='x' is not focusable"
            'help' => 'regulation.location.administrator.help',
            'choices' => array_merge(
                ['regulation.location.administrator.placeholder' => ''],
                $choices,
            ),
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default', 'html_form'],
            'administrators' => [],
            'feature_road_type' => false,
        ]);
        $resolver->setAllowedTypes('administrators', 'array');
    }
}
