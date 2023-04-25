<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AddUserFormType extends AbstractType
{
    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('fullName',
            TextType::class,
            options: [
                'label' => 'user.fullname',
            ],
        )
        ->add('email',
            EmailType::class,
            options: [
                'label' => 'user.email',
            ],
        )
        ->add('password',
            RepeatedType::class,
            options: [
                'type' => PasswordType::class,
                    'invalid_message' => 'Le mot de passe ne correspond pas',
                    'required' => true,
                    'options' => ['attr' => [
                        'class' => 'fr-input', ]],
                    'first_options' => ['label' => 'login.password'],
                    'second_options' => ['label' => 'password.confirm',
                    ],
            ],
        )
        ->add('save',
            SubmitType::class,
            options: [
                'label' => 'common.form.save',
            ],
        );
    }
}
