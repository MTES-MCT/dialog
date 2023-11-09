<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FeedbackFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'content',
                TextType::class,
                options: [
                    'label' => 'feedback.content.label',
                ],
            )
            ->add(
                'consentToBeContacted',
                CheckboxType::class, [
                    'label' => 'feedback.consenttobecontacted.label',
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
