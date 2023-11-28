<?php

declare(strict_types=1);

namespace App\Infrastructure\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface as FormFormBuilderInterface;
use Symfony\Component\Form\Test\FormBuilderInterface;


final class GeoPlateformeDataFormType extends AbstractType
{
    public function buildForm(FormFormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address',
            TextType::class,
            options: [
                'label' => 'nom de la commune',
                'required' => false,
                
            ],
            )
            ->add('longitude',
            NumberType::class,
            options: [
                'label' => 'Longitude',
                'required' => false,
            ],
            )
            ->add('latitude',
            NumberType::class,
            options: [
                'label' => 'latitude',
                'required' => false,
            ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'chercher',
                ],
            )
            ->add(
                'result',
                TextType::class,
                options: [
                    'label' => 'résultat de la requête',
                    'required' => false,
                ],
            )
        ;
    }
}
