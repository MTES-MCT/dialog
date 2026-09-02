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
                    'choices' => [
                        'regulation.location.road.type.lane' => RoadTypeEnum::LANE->value,
                        'regulation.location.road.type.rawGeoJSON' => RoadTypeEnum::RAW_GEOJSON->value,
                    ],
                    'label' => 'regulation.location.type',
                    'label_attr' => ['class' => 'required'],
                ],
            )
            ->add('namedStreet', NamedStreetFormType::class, [
                'with_city' => $options['with_city'],
                'label' => false,
            ])
            ->add('rawGeoJSON', RawGeoJSONFormType::class, [
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Pour une exception de « Ville entière », la ville est héritée de la localisation
            // parente ; pour un tracé de zone ou un tracé libre, l'utilisateur la choisit.
            'with_city' => false,
            'validation_groups' => ['Default', 'html_form'],
            'data_class' => SaveWholeCityExceptionCommand::class,
        ]);
        $resolver->setAllowedTypes('with_city', 'bool');
    }
}
