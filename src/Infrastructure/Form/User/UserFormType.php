<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'fullName',
                TextType::class,
                options: [
                    'label' => 'user.list.fullName',
                ],
            )
            ->add(
                'email',
                EmailType::class,
                options: [
                    'label' => 'user.list.email',
                    'help' => 'login.email_format',
                ],
            )
        ;

        if ($options['is_owner_visible']) {
            $builder->add(
                'isOwner',
                CheckboxType::class,
                options: [
                    'label' => 'user.form.is_owner',
                    'required' => false,
                ],
            );
        }

        $builder->add('save', SubmitType::class,
            options: [
                'label' => 'common.save',
                'attr' => ['class' => 'fr-btn'],
            ],
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_owner_visible' => false,
        ]);
    }
}
