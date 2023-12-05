<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class AccessRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'fullName',
                TextType::class,
                options: [
                    'label' => 'accessRequest.fullName',
                ],
            )
            ->add(
                'email',
                EmailType::class,
                options: [
                    'label' => 'accessRequest.email',
                    'help' => 'accessRequest.email.help',
                ],
            )
            ->add(
                'organizationName',
                TextType::class,
                options: [
                    'label' => 'accessRequest.organizationName',
                ],
            )
            ->add(
                'organizationSiret',
                TextType::class,
                options: [
                    'label' => 'accessRequest.organizationSiret',
                    'help' => 'accessRequest.organizationSiret.help',
                    'required' => false,
                ],
            )
            ->add(
                'password',
                PasswordType::class,
                options: [
                    'label' => 'accessRequest.password',
                    'help' => 'accessRequest.password.help',
                ],
            )
            ->add(
                'comment',
                TextareaType::class,
                options: [
                    'label' => 'accessRequest.comment',
                    'required' => false,
                ],
            )
            ->add(
                'consentToBeContacted',
                CheckboxType::class, [
                    'label' => 'accessRequest.consentToBeContacted',
                    'required' => false,
                ],
            )
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'common.send',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
