<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class CreateAccountFromInvitationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'password',
                RepeatedType::class,
                options: [
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'register.password', 'help' => 'register.password.help'],
                    'second_options' => ['label' => 'register.password.confirm', 'help' => 'register.password.help'],
                ],
            )
            ->add(
                'cgu',
                CheckboxType::class, [
                    'label' => 'register.cgu',
                    'required' => true,
                    'mapped' => false,
                ],
            )
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'invitation.register.submit',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
