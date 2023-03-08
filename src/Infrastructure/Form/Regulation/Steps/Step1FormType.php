<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation\Steps;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class Step1FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'issuingAuthority',
                TextType::class,
                options: [
                    'label' => 'regulation.step1.issuing_authority',
                    'help' => 'regulation.step1.issuing_authority.help',
                ],
            )
            ->add(
                'description',
                TextareaType::class,
                options: [
                    'label' => 'regulation.step1.description',
                    'help' => 'regulation.step1.description.help',
                    'attr' => [
                        'class' => 'fr-input'
                    ]
                    // widget_class not working. Only way to apply a class on the component textarea
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.next',
                ],
            )
        ;
    }
}
