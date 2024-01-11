<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Domain\Regulation\Enum\LocationTypeEnum;
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
        $builder
        ->add(
            'roadType',
            ChoiceType::class,
            options: $this->getTypeOptions(),
        )
        ->add(
            'administrator',
            TextType::class,
            options: [
                'label' => 'regulation.location.administrator',
                'help' => 'regulation.location.administrator.help',
            ],
        )
        ->add(
            'roadNumber',
            TextType::class,
            options: [
                'label' => 'Numéro de la départementale',
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
            )
        ;
    }

    private function getTypeOptions(): array
    {
        $choices = [];

        foreach (LocationTypeEnum::cases() as $case) {
            $choices[sprintf('regulation.location.road.type.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => array_merge(
                ['regulation.location.type.placeholder' => ''],
                $choices,
            ),
            'label' => 'regulation.location.type',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default', 'html_form'],
        ]);
    }
}
