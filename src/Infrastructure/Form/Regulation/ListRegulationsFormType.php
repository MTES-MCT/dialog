<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ListRegulationsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'identifier',
                TextType::class,
                options: [
                    'label' => 'regulation.list.filters.identifier',
                    'required' => false,
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.search',
                    'attr' => [
                        'class' => 'fr-btn fr-btn-load',
                    ],
                ],
            )
        ;
    }
}
