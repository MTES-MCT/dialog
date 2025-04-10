<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class RegulationOrderTemplateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                options: [
                    'label' => 'regulation_order_template.name',
                    'help' => 'regulation_order_template.name.help',
                ],
            )
            ->add(
                'title',
                TextareaType::class,
                options: [
                    'label' => 'regulation_order_template.title',
                ],
            )
            ->add(
                'visaContent',
                TextareaType::class,
                options: [
                    'label' => 'regulation_order_template.visaContent',
                ],
            )
            ->add(
                'consideringContent',
                TextareaType::class,
                options: [
                    'label' => 'regulation_order_template.consideringContent',
                ],
            )
            ->add(
                'articleContent',
                TextareaType::class,
                options: [
                    'label' => 'regulation_order_template.articleContent',
                ],
            )

            ->add('save', SubmitType::class,
                options: [
                    'label' => 'common.save',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
