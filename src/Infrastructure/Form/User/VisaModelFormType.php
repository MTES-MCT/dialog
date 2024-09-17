<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class VisaModelFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $builder->getData();
        $builder
            ->add(
                'name',
                TextType::class,
                options: [
                    'label' => 'visa.name',
                    'help' => 'visa.name.help',
                ],
            )
            ->add(
                'description',
                TextareaType::class,
                options: [
                    'label' => 'visa.description',
                    'required' => false,
                ],
            )
            ->add(
                'visas',
                CollectionType::class,
                options: [
                    'entry_type' => TextareaType::class,
                    'label' => null,
                    'prototype_name' => '__visa_name__',
                    'entry_options' => [
                        'label' => 'visas.form.visa',
                    ],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'keep_as_list' => true,
                    'data' => $data->visas ?: [''],
                    'error_bubbling' => false,
                ],
            )
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'common.save',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
