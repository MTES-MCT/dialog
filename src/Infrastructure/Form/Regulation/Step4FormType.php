<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class Step4FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'maxWeight',
                NumberType::class,
                options: [
                    'label' => 'regulation.step4.max_weight',
                    'required' => false,
                ],
            )
            ->add(
                'maxHeight',
                NumberType::class,
                options: [
                    'label' => 'regulation.step4.max_height',
                    'required' => false,
                ],
            )
            ->add(
                'maxWidth',
                NumberType::class,
                options: [
                    'label' => 'regulation.step4.max_width',
                    'required' => false,
                ],
            )
            ->add(
                'maxLength',
                NumberType::class,
                options: [
                    'label' => 'regulation.step4.max_length',
                    'required' => false,
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
