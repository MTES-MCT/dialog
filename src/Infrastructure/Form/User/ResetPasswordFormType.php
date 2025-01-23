<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'reset_password.password',
                    'help' => 'reset_password.password.help',
                ],
                'second_options' => [
                    'label' => 'reset_password.repeated_password',
                ],
            ])
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'reset_password.submit',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
