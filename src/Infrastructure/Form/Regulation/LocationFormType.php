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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LocationFormType extends AbstractType
{
    private const FORM_FIELDS = [
        'departmentalRoad' => NumberedRoadFormType::class,
        'nationalRoad' => NumberedRoadFormType::class,
        'namedStreet' => NamedStreetFormType::class,
        'rawGeoJSON' => RawGeoJSONFormType::class,
    ];

    private const ROAD_TYPE_TO_FORM_FIELD = [
        RoadTypeEnum::DEPARTMENTAL_ROAD->value => 'departmentalRoad',
        RoadTypeEnum::NATIONAL_ROAD->value => 'nationalRoad',
        RoadTypeEnum::LANE->value => 'namedStreet',
        RoadTypeEnum::RAW_GEOJSON->value => 'rawGeoJSON',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addRoadTypeChoice($builder, $options['permissions'])
            ->addSubForms($builder, $options['administrators'])
            ->addFormEventListeners($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => SaveLocationCommand::class,
                'administrators' => $this->getDefaultAdministrators(),
                'permissions' => [],
                'validation_groups' => $this->getValidationGroups(),
            ])
            ->setAllowedTypes('administrators', 'array')
            ->setAllowedTypes('permissions', 'array');
    }

    private function addRoadTypeChoice(FormBuilderInterface $builder, array $permissions): self
    {
        $builder->add('roadType', ChoiceType::class, [
            'choices' => $this->getRoadTypeChoices($permissions),
            'label' => 'regulation.location.type',
            'label_attr' => ['class' => 'required'],
        ]);

        return $this;
    }

    private function addSubForms(FormBuilderInterface $builder, array $administrators): self
    {
        foreach (self::FORM_FIELDS as $field => $formType) {
            $options = [
                'mapped' => false,
                'label' => false,
            ];

            if ($formType === NumberedRoadFormType::class) {
                $roadType = str_contains($field, 'departmental')
                    ? RoadTypeEnum::DEPARTMENTAL_ROAD->value
                    : RoadTypeEnum::NATIONAL_ROAD->value;

                $options += [
                    'roadType' => $roadType,
                    'administrators' => $administrators[$roadType] ?? [],
                ];
            }

            $builder->add($field, $formType, $options);
        }

        return $this;
    }

    private function addFormEventListeners(FormBuilderInterface $builder): self
    {
        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                fn (FormEvent $event) => $this->onPreSetData($event),
            )
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                fn (FormEvent $event) => $this->onPreSubmit($event),
            );

        return $this;
    }

    private function onPreSetData(FormEvent $event): void
    {
        $data = $event->getData();
        if (!$data) {
            return;
        }

        $this->updateFormMapping($event->getForm(), $data->getRoadType());
    }

    private function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        if (!isset($data['roadType'])) {
            return;
        }

        $this->updateFormMapping($event->getForm(), $data['roadType']);
    }

    private function updateFormMapping(FormInterface $form, string $roadType): void
    {
        // Reset all mappings to false
        foreach (self::FORM_FIELDS as $field => $formType) {
            $this->updateFormField($form, $field, false);
        }

        // Set the active form mapping to true
        if ($activeField = self::ROAD_TYPE_TO_FORM_FIELD[$roadType] ?? null) {
            $this->updateFormField($form, $activeField, true);
        }
    }

    private function updateFormField(FormInterface $form, string $field, bool $mapped): void
    {
        if (!$form->has($field)) {
            return;
        }

        $config = $form->get($field)->getConfig();
        $form->add($field, \get_class($config->getType()->getInnerType()), [
            'mapped' => $mapped,
            'label' => false,
        ] + $config->getOptions());
    }

    private function getRoadTypeChoices(array $permissions): array
    {
        $choices = ['regulation.location.type.placeholder' => ''];

        foreach (RoadTypeEnum::cases() as $case) {
            if ($case->value === RoadTypeEnum::RAW_GEOJSON->value
                && !\in_array(CanUseRawGeoJSON::PERMISSION_NAME, $permissions, true)) {
                continue;
            }

            $choices[\sprintf('regulation.location.road.type.%s', $case->value)] = $case->value;
        }

        return $choices;
    }

    private function getDefaultAdministrators(): array
    {
        return [
            RoadTypeEnum::DEPARTMENTAL_ROAD->value => [],
            RoadTypeEnum::NATIONAL_ROAD->value => [],
        ];
    }

    private function getValidationGroups(): callable
    {
        return static function (FormInterface $form): array {
            return ['Default', $form->getData()?->getRoadType() ?? ''];
        };
    }
}
