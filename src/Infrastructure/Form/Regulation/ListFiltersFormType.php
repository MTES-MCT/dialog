<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ListFiltersFormType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'organization',
                ChoiceType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.list.organization',
                    'placeholder' => 'regulation.list.all_organizations',
                    'choices' => $options['organizations'],
                    'choice_value' => 'uuid',
                    'choice_label' => 'name',
                ],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'organizations' => [],
            'csrf_protection' => false,
        ]);
        $resolver->setAllowedTypes('organizations', 'array');
    }
}
