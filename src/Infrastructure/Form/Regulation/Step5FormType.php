<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class Step5FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'status',
                ChoiceType::class,
                options: [
                    'choices' => [
                        'regulation.step5.draft' => 'draft',
                        'regulation.step5.published' => 'published',
                    ],
                    'expanded' => true,
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.save',
                ],
            )
        ;
    }
}
