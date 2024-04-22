<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LocationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'roadType',
                ChoiceType::class,
                options: $this->getRoadTypeOptions(),
            )
            ->add('numberedRoad', NumberedRoadFormType::class, [
                'administrators' => $options['administrators'],
            ])
            ->add('namedStreet', NamedStreetFormType::class)
        ;
    }

    private function getRoadTypeOptions(): array
    {
        $choices = [];

        foreach (RoadTypeEnum::cases() as $case) {
            $choices[sprintf('regulation.location.road.type.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => array_merge(
                ['regulation.location.type.placeholder' => ''],
                $choices,
            ),
            'label' => 'regulation.location.type',
            'label_attr' => [
                'class' => 'required',
            ],
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'administrators' => [],
            'data_class' => SaveLocationCommand::class,
        ]);
        $resolver->setAllowedTypes('administrators', 'array');
    }
}
