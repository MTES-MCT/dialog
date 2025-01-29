<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class ForgotPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'register.email',
            ])
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'common.submit',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
