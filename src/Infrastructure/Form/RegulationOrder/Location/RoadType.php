<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\RegulationOrder\Location;

use App\Domain\Condition\Location\Road;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RoadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                options: [
                    'label' => 'regulation_order.form.roads.name',
                ],
            )
            ->add(
                'fromHouseNumber',
                NumberType::class,
                options: [
                    'label' => 'regulation_order.form.roads.from_house_number',
                ],
            )
            ->add(
                'toHouseNumber',
                NumberType::class,
                options: [
                    'label' => 'regulation_order.form.roads.to_house_number',
                ],
            )
            ->add(
                'remove',
                ButtonType::class,
                options: [
                    'label' => 'regulation_order.form.roads.remove',
                ],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Road::class,
        ]);
    }
}
