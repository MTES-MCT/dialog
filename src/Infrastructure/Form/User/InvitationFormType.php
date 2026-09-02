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

final class InvitationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isMandataireOptions = [
            'label' => 'user.form.is_mandataire.invite',
            'help' => 'user.form.is_mandataire.help',
            'required' => false,
        ];

        // L'invitation émise par un mandataire est forcément une invitation de mandataire.
        if ($options['mandataire_forced']) {
            $isMandataireOptions['disabled'] = true;
            $isMandataireOptions['data'] = true;
        }

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
            ->add('isMandataire', CheckboxType::class, $isMandataireOptions)
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'common.invite',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mandataire_forced' => false,
        ]);
    }
}
