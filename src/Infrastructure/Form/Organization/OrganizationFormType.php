<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class OrganizationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                options: [
                    'label' => 'organization.form.name',
                ],
            )
            ->add(
                'address',
                TextType::class,
                options: [
                    'label' => 'organization.establishmentAddress',
                ],
            )
            ->add(
                'zipCode',
                TextType::class,
                options: [
                    'label' => 'organization.zipCode',
                ],
            )
            ->add(
                'city',
                TextType::class,
                options: [
                    'label' => 'organization.city',
                ],
            )
            ->add(
                'addressComplement',
                TextType::class,
                options: [
                    'label' => 'organization.addressComplement',
                    'required' => false,
                ],
            )
            ->add(
                'file',
                FileType::class,
                options: [
                    'label' => 'organization.image',
                    'help' => 'organization.image.help',
                    'required' => false,
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
