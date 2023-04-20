<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GeneralInfoFormType extends AbstractType
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'identifier',
                TextType::class,
                options: [
                    'label' => 'regulation.general_info.identifier',
                    'help' => 'regulation.general_info.identifier.help',
                ],
            )
            ->add(
                'startDate',
                DateType::class,
                options: [
                    'label' => 'regulation.general_info.start_date',
                    'help' => 'regulation.general_info.start_date.help',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
                ],
            )
            ->add(
                'endDate',
                DateType::class,
                options: [
                    'label' => 'regulation.general_info.end_date',
                    'help' => 'regulation.general_info.end_date.help',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
                    'required' => false,
                ],
            )
            ->add(
                'organization',
                ChoiceType::class,
                options: [
                    'label' => 'regulation.general_info.organization',
                    'help' => 'regulation.general_info.organization.help',
                    'choices' => $options['organizations'],
                    'choice_value' => 'uuid',
                    'choice_label' => 'name',
                ],
            )
            ->add(
                'category',
                ChoiceType::class,
                options: [
                    'label' => 'regulation.general_info.category',
                    'help' => 'regulation.general_info.cause.help',
                    'choices' => [
                      'regulation.cause1' => 'category_travaux',
                      'regulation.cause2' => 'category_evenement',
                    ],
                ],
            )
            ->add(
                'description',
                TextareaType::class,
                options: [
                    'label' => 'regulation.general_info.description',
                    'help' => 'regulation.general_info.description.help',
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: $options['save_options'],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default', 'html_form'],
            'organizations' => [],
            'save_options' => [],
        ]);
        $resolver->setAllowedTypes('organizations', 'array');
        $resolver->setAllowedTypes('save_options', 'array');
    }
}
