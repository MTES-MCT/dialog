<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\PointTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NamedStreetFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'cityCode',
                HiddenType::class,
            )
            ->add(
                'cityLabel',
                TextType::class,
                options: [
                    'label' => 'regulation.location.city',
                ],
            )
            ->add(
                'roadBanId',
                HiddenType::class,
            )
            ->add(
                'roadName',
                TextType::class,
                options: [
                    'label' => 'regulation.location.roadName',
                    'help' => 'regulation.location.roadName.help',
                ],
            )
            ->add(
                'isEntireStreet',
                CheckboxType::class,
                options: [
                    'label' => 'regulation.location.isEntireStreet',
                    'required' => false,
                ],
            )
            ->add(
                'fromPointType',
                ChoiceType::class,
                options: $this->getPointTypeOptions(),
            )
            ->add(
                'fromHouseNumber',
                TextType::class,
                options: [
                    'label' => 'regulation.location.named_street.house_number',
                ],
            )
            ->add(
                'fromRoadName',
                TextType::class,
                options: [
                    'label' => 'regulation.location.named_street.intersection',
                ],
            )
            ->add(
                'toPointType',
                ChoiceType::class,
                options: $this->getPointTypeOptions(),
            )
            ->add(
                'toHouseNumber',
                TextType::class,
                options: [
                    'label' => 'regulation.location.named_street.house_number',
                ],
            )
            ->add(
                'toRoadName',
                TextType::class,
                options: [
                    'label' => 'regulation.location.named_street.intersection',
                ],
            )
            ->add('direction', ChoiceType::class, $this->getDirectionOptions())
            ->add('roadType', HiddenType::class)
        ;

        // Constraint "Valid" cannot be nested inside constraint When. The event listener is used to ensure that the roadType is added to the submitted data before the form is processed.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $data['roadType'] = $event->getForm()->getParent()->get('roadType')->getData();
            $data['direction'] = $data['direction'] ?? DirectionEnum::BOTH->value; // Prevent null if entire street is checked
            $event->setData($data);
        });
    }

    private function getPointTypeOptions(): array
    {
        $choices = [];

        foreach (PointTypeEnum::cases() as $case) {
            $choices[\sprintf('regulation.location.named_street.point_type.%s', $case->value)] = $case->value;
        }

        $choices = array_merge(
            ['regulation.location.named_street.point_type.placeholder' => ''],
            $choices,
        );

        return [
            'choices' => $choices,
            'choice_attr' => [
                'regulation.location.named_street.point_type.placeholder' => [
                    'disabled' => true,
                ],
            ],
            'label' => 'regulation.location.named_street.point_type',
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
            'data_class' => SaveNamedStreetCommand::class,
            'error_mapping' => [
                'cityCode' => 'cityLabel',
            ],
        ]);
    }
}
