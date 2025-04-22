<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadSideEnum;
use App\Domain\Regulation\Location\StorageArea;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NumberedRoadFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'administrator',
                ChoiceType::class,
                options: $this->getAdministratorOptions($options['administrators'], $options['roadType']),
            )
            ->add(
                'roadNumber',
                TextType::class,
                options: [
                    'label' => 'regulation.location.roadNumber.' . $options['roadType'],
                    'help' => 'regulation.location.roadNumber.help.' . $options['roadType'],
                ],
            )
            ->add(
                'fromPointNumber',
                HiddenType::class,
                options: [
                    // Keep previous 'fromPointNumber' field name in HTML
                    'property_path' => 'fromPointNumberWithDepartmentCode',
                ],
            )
            ->add(
                'fromPointNumberWithDepartmentCodeLabel',
                TextType::class,
                options: [
                    'label' => 'regulation.location.referencePoint.pointNumber',
                    'help' => 'regulation.location.referencePoint.pointNumber.help',
                ],
            )
            ->add(
                'fromSide',
                ChoiceType::class,
                options: $this->getRoadSideOptions(),
            )
            ->add(
                'toPointNumber',
                HiddenType::class,
                options: [
                    // Keep previous 'toPointNumber' field name in HTML
                    'property_path' => 'toPointNumberWithDepartmentCode',
                ],
            )
            ->add(
                'toPointNumberWithDepartmentCodeLabel',
                TextType::class,
                options: [
                    'label' => 'regulation.location.referencePoint.pointNumber',
                    'help' => 'regulation.location.referencePoint.pointNumber.help',
                ],
            )
            ->add(
                'toSide',
                ChoiceType::class,
                options: $this->getRoadSideOptions(),
            )
            ->add(
                'fromAbscissa',
                IntegerType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.location.referencePoint.abscissa',
                    'help' => 'regulation.location.referencePoint.abscissa.help',
                ],
            )
            ->add(
                'toAbscissa',
                IntegerType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.location.referencePoint.abscissa',
                    'help' => 'regulation.location.referencePoint.abscissa.help',
                ],
            )
            ->add('direction', ChoiceType::class, $this->getDirectionOptions())
            ->add('roadType', HiddenType::class, ['data' => $options['roadType']])
        ;

        // Credit: https://symfony.com/doc/current/form/dynamic_form_modification.html#dynamic-generation-for-submitted-forms
        $storageAreaModifier = function (FormInterface $form, mixed $data = null) use ($options) {
            $choices = [];

            if ($data === null) {
                // If no road selected, allow selecting from all storage areas
                foreach ($options['storage_areas'] as $roadNumber => $storageAreas) {
                    $choices[] = $storageAreas;
                }
            } elseif (\array_key_exists($data->roadNumber, $options['storage_areas'])) {
                // Or get only the storage areas of selected road
                foreach ($options['storage_areas'][$data->roadNumber] as $storageArea) {
                    $choices[] = $storageArea;
                }
            }

            $form->add('storageArea', EntityType::class, [
                'class' => StorageArea::class,
                'choices' => $choices,
                'choice_label' => 'description',
                'choice_value' => 'uuid',
                'label' => 'regulation.location.storage_area',
                'help' => 'regulation.location.storage_area.help',
                'placeholder' => 'regulation.location.storage_area.placeholder',
                'required' => false,
                'label_attr' => [
                    'class' => 'required',
                ],
            ]);
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($storageAreaModifier): void {
            $storageAreaModifier($event->getForm(), $event->getData());
        });

        // Constraint "Valid" cannot be nested inside constraint When. The event listener is used to ensure that the roadType is added to the submitted data before the form is processed.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($storageAreaModifier): void {
            $data = $event->getData();
            $data['roadType'] = $event->getForm()->getParent()->get('roadType')->getData();
            $data['direction'] = $data['direction'] ?? DirectionEnum::BOTH->value;
            $event->setData($data);

            $storageAreaModifier($event->getForm(), $event->getForm()->getData());
        });
    }

    private function getRoadSideOptions(): array
    {
        $choices = [];

        foreach (RoadSideEnum::cases() as $case) {
            $choices[\sprintf('regulation.location.road.side.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => array_merge(
                $choices,
            ),
            'label' => 'regulation.location.road.side',
            'help' => 'regulation.location.road.side.help',
        ];
    }

    private function getAdministratorOptions(array $administrators, string $roadType): array
    {
        $choices = [];

        foreach ($administrators as $value) {
            $choices[$value] = $value;
        }

        return [
            'label' => 'regulation.location.administrator',
            'help' => \sprintf('regulation.location.administrator.help.%s', $roadType),
            'choices' => array_merge(
                ['regulation.location.administrator.placeholder' => ''],
                $choices,
            ),
        ];
    }

    private function getDirectionOptions(): array
    {
        $choices = [];

        foreach (DirectionEnum::cases() as $case) {
            $choices[\sprintf('regulation.location.direction.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.location.direction',
            'help' => 'regulation.location.direction.help',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'roadType' => null,
            'administrators' => [],
            'storage_areas' => [],
            'data_class' => SaveNumberedRoadCommand::class,
            'error_mapping' => [
                'fromPointNumber' => 'fromPointNumberWithDepartmentCodeLabel',
                'toPointNumber' => 'toPointNumberWithDepartmentCodeLabel',
            ],
        ]);
        $resolver->setAllowedTypes('roadType', 'string');
        $resolver->setAllowedTypes('administrators', 'array');
        $resolver->setAllowedTypes('storage_areas', 'array');
    }
}
