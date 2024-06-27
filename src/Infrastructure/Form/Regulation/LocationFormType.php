<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Specification\CanUseRawGeoJSON;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                'label' => false,
            ])
            ->add('namedStreet', NamedStreetFormType::class, [
                'label' => false,
            ])
            ->add('rawGeoJSON', RawGeoJSONFormType::class, [
                'label' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $command = $event->getData();

            $isRawGeoJSON = $command?->roadType === RoadTypeEnum::RAW_GEOJSON->value;
            $canUseRawGeoJSON = \in_array(CanUseRawGeoJSON::PERMISSION_NAME, $options['permissions']);

            if ($isRawGeoJSON || $canUseRawGeoJSON) {
                // Replace field with new options
                $form->add(
                    'roadType',
                    ChoiceType::class,
                    options: $this->getRoadTypeOptions(
                        includeRawGeoJSONOption: true,
                    ),
                );
            }
        });
    }

    private function getRoadTypeOptions(bool $includeRawGeoJSONOption = false): array
    {
        $choices = [];
        $choiceAttr = [];

        foreach (RoadTypeEnum::cases() as $case) {
            $label = sprintf('regulation.location.road.type.%s', $case->value);

            if ($case->value === RoadTypeEnum::RAW_GEOJSON->value && !$includeRawGeoJSONOption) {
                $choiceAttr[$label] = [
                    'hidden' => '',
                    'disabled' => 'disabled', // For Safari (it does not support <option hidden>)
                ];
            }

            $choices[$label] = $case->value;
        }

        return [
            'choices' => array_merge(
                ['regulation.location.type.placeholder' => ''],
                $choices,
            ),
            'choice_attr' => $choiceAttr,
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
            'permissions' => [],
            'data_class' => SaveLocationCommand::class,
        ]);
        $resolver->setAllowedTypes('administrators', 'array');
        $resolver->setAllowedTypes('permissions', 'array');
    }
}
