<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

final class FeedbackFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'content',
                TextareaType::class,
                options: [
                    'label' => 'feedback.content.label',
                    'help' => 'feedback.content.help',
                ],
            )
            ->add(
                'consentToBeContacted',
                CheckboxType::class, [
                    'label' => 'feedback.consenttobecontacted.label',
                    'help' => 'feedback.consenttobecontacted.help',
                    'required' => false,
                ],
            )
            ->add('save', SubmitType::class,
                options: [
                'label' => 'Envoyer',
                'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
