<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class LogoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'file',
                FileType::class,
                options: [
                    'label' => ' ',
                    'required' => false,
                ],
            )
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'organization.logo.button',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
