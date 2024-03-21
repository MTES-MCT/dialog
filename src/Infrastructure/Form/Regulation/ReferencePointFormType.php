<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

final class ReferencePointFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'pointNumber',
                IntegerType::class,
                options: [
                    'label' => 'regulation.location.referencePoint.pointNumber',
                    'help' => 'regulation.location.referencePoint.pointNumber.help',
                    'attr' => [
                        'min' => 0,
                        'step' => 1,
                    ],
                ],
            )
            ->add(
                'abscissa',
                IntegerType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.location.referencePoint.abscissa',
                    'help' => 'regulation.location.referencePoint.abscissa.help',
                    'attr' => [
                        'min' => 0,
                        'step' => 1,
                    ],
                ],
            );
    }
}
