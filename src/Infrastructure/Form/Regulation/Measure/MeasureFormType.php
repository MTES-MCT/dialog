<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation\Measure;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\User\Organization;
use App\Infrastructure\Form\Regulation\LocationFormType;
use App\Infrastructure\Form\Regulation\PeriodFormType;
use App\Infrastructure\Form\Regulation\VehicleSetFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MeasureFormType extends AbstractType
{
    public const NAME = 'measure_form';

    /**
     * Nom du formulaire, dont dérivent les attributs name et id de tous ses champs.
     *
     * Plusieurs formulaires de mesure peuvent être ouverts en même temps sur la page d'un arrêté
     * (modification d'une mesure pendant l'ajout d'une autre, par exemple). Sans nom propre à chaque
     * mesure, leurs identifiants HTML se recoupent et les labels, le bouton « Valider » et les
     * sélecteurs d'un formulaire agissent sur le premier formulaire de la page.
     */
    public static function getName(?string $measureUuid = null): string
    {
        return $measureUuid === null ? self::NAME : \sprintf('%s_%s', self::NAME, $measureUuid);
    }

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
                    'label' => 'regulation.measure.maxSpeed.title',
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add('vehicleSet', VehicleSetFormType::class)
            ->add('periods', CollectionType::class, [
                'entry_type' => PeriodFormType::class,
                'entry_options' => [
                    'label' => false,
                    'isPermanent' => $options['isPermanent'],
                ],
                'prototype_name' => '__period_name__',
                'label' => 'regulation.period_list',
                'help' => 'regulation.period_list.help',
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])
            ->add('locations', CollectionType::class, [
                'entry_type' => LocationFormType::class,
                'entry_options' => [
                    'label' => false,
                    'administrators' => $options['administrators'],
                    'storage_areas' => $options['storage_areas'],
                    'permissions' => $options['permissions'],
                    'organization' => $options['organization'],
                ],
                'prototype_name' => '__location_name__',
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.validate',
                ],
            )
        ;
    }

    private function getTypeOptions(): array
    {
        $displayOrder = [
            MeasureTypeEnum::NO_ENTRY,
            MeasureTypeEnum::SPEED_LIMITATION,
            MeasureTypeEnum::PARKING_PROHIBITED,
            MeasureTypeEnum::ALTERNATE_ROAD,
            MeasureTypeEnum::NO_OVERTAKING,
        ];

        $choices = [];
        foreach ($displayOrder as $case) {
            $choices[\sprintf('regulation.measure.type.%s', $case->value)] = $case->value;
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
            'administrators' => [],
            'storage_areas' => [],
            'isPermanent' => false,
            'permissions' => [],
            'organization' => null,
            'validation_groups' => ['Default', 'html_form'],
        ]);
        $resolver->setAllowedTypes('administrators', 'array');
        $resolver->setAllowedTypes('storage_areas', 'array');
        $resolver->setAllowedTypes('isPermanent', 'boolean');
        $resolver->setAllowedTypes('permissions', 'array');
        $resolver->setAllowedTypes('organization', ['null', Organization::class]);
    }
}
