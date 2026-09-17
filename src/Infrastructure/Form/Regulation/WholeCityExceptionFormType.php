<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WholeCityExceptionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'roadType',
                ChoiceType::class,
                options: [
                    'choices' => $this->getRoadTypeChoices(),
                    'label' => 'regulation.location.type',
                    'label_attr' => ['class' => 'required'],
                ],
            )
            ->add('namedStreet', NamedStreetFormType::class, [
                'with_city' => $options['with_city'],
                'label' => false,
            ])
            ->add(RoadTypeEnum::DEPARTMENTAL_ROAD->value, NumberedRoadFormType::class, [
                'roadType' => RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'administrators' => $options['administrators'][RoadTypeEnum::DEPARTMENTAL_ROAD->value],
                'with_storage_area' => false,
                'label' => false,
            ])
            ->add(RoadTypeEnum::NATIONAL_ROAD->value, NumberedRoadFormType::class, [
                'roadType' => RoadTypeEnum::NATIONAL_ROAD->value,
                'administrators' => $options['administrators'][RoadTypeEnum::NATIONAL_ROAD->value],
                'with_storage_area' => false,
                'label' => false,
            ])
            ->add('zone', ZoneFormType::class, [
                'label' => false,
            ])
            ->add('rawGeoJSON', RawGeoJSONFormType::class, [
                'label' => false,
            ])
        ;
    }

    private function getRoadTypeChoices(): array
    {
        // Liste explicite plutôt qu'une itération de l'enum : un nouveau cas de RoadTypeEnum
        // ne doit apparaître ici qu'accompagné de son sous-formulaire et de sa prise en
        // charge dans SaveWholeCityExceptionCommand — sinon il serait proposé puis
        // silencieusement ignoré à l'enregistrement. « Ville entière » n'est pas proposée :
        // une exception ne peut pas être elle-même une ville entière.
        $supportedTypes = [
            RoadTypeEnum::LANE,
            RoadTypeEnum::DEPARTMENTAL_ROAD,
            RoadTypeEnum::NATIONAL_ROAD,
            RoadTypeEnum::RAW_GEOJSON,
            RoadTypeEnum::ZONE,
        ];

        $choices = [];
        foreach ($supportedTypes as $case) {
            $choices[\sprintf('regulation.location.road.type.%s', $case->value)] = $case->value;
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Pour une exception de « Ville entière », la ville est héritée de la localisation
            // parente ; pour un tracé de zone ou un tracé libre, l'utilisateur la choisit.
            'with_city' => false,
            'administrators' => [
                RoadTypeEnum::DEPARTMENTAL_ROAD->value => [],
                RoadTypeEnum::NATIONAL_ROAD->value => [],
            ],
            'validation_groups' => ['Default', 'html_form'],
            'data_class' => SaveWholeCityExceptionCommand::class,
        ]);
        $resolver->setAllowedTypes('with_city', 'bool');
        $resolver->setAllowedTypes('administrators', 'array');
    }
}
