<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class SigningAuthorityFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                options: [
                    'label' => 'signing_authority.name',
                    'help' => 'signing_authority.name.help',
                ],
            )
            ->add(
                'address',
                TextareaType::class,
                options: [
                    'label' => 'signing_authority.address',
                    'help' => 'signing_authority.address.help',
                ],
            )
            ->add(
                'placeOfSignature',
                TextType::class,
                options: [
                    'label' => 'signing_authority.placeOfSignature',
                    'help' => 'signing_authority.placeOfSignature.help',
                ],
            )
            ->add(
                'signatoryName',
                TextType::class,
                options: [
                    'label' => 'signing_authority.signatoryName',
                    'help' => 'signing_authority.signatoryName.help',
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
