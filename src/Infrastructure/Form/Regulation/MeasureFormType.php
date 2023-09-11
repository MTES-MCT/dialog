<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MeasureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'type',
                ChoiceType::class,
                options: $this->getTypeOptions(),
            )
            ->add(
                'maxSpeed',
                NumberType::class,
                options: [
                    'required' => false,
                    'label' => 'Vitesse maximale autorisée *',
                ],
            )
            ->add('vehicleSet', VehicleSetFormType::class)
            ->add('periods', CollectionType::class, [
                'entry_type' => PeriodFormType::class,
                'entry_options' => ['label' => false],
                'prototype_name' => '__period_name__',
                'label' => 'regulation.period_list',
                'help' => 'regulation.period_list.help',
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])
        ;
    }

    private function getTypeOptions(): array
    {
        $choices = [];

        foreach (MeasureTypeEnum::cases() as $case) {
            $choices[sprintf('regulation.measure.type.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => array_merge(
                ['regulation.measure.type.placeholder' => ''],
                $choices,
            ),
            'label' => 'regulation.measure.type',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveMeasureCommand::class,
        ]);
    }
}
