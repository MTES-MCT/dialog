<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
                'address',
                TextType::class,
                options: [
                    'label' => 'regulation.location.address',
                    'help' => 'regulation.location.address.help',
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default', 'html_form'],
        ]);
    }
}
