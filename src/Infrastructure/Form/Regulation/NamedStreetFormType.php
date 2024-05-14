<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
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
                    'label_attr' => [
                        'class' => 'required',
                    ],
                    'required' => false,
                ],
            )
            ->add(
                'roadName',
                TextType::class,
                options: [
                    'label' => 'regulation.location.roadName',
                    'help' => 'regulation.location.roadName.help',
                    'required' => false,
                    'label_attr' => [
                        'class' => 'required',
                    ],
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
                    'required' => false,
                    'label' => 'regulation.location.named_street.house_number',
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add(
                'fromRoadName',
                TextType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.location.named_street.intersection',
                    'label_attr' => [
                        'class' => 'required',
                    ],
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
                    'required' => false,
                    'label' => 'regulation.location.named_street.house_number',
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add(
                'toRoadName',
                TextType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.location.named_street.intersection',
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add('roadType', HiddenType::class)
        ;

        // Constraint "Valid" cannot be nested inside constraint When. The event listener is used to ensure that the roadType is added to the submitted data before the form is processed.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $data['roadType'] = $event->getForm()->getParent()->get('roadType')->getData();
            $event->setData($data);
        });
    }

    private function getPointTypeOptions(): array
    {
        $choices = [];

        foreach (PointTypeEnum::cases() as $case) {
            $choices[sprintf('regulation.location.named_street.point_type.%s', $case->value)] = $case->value;
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
            'required' => false,
            'label' => 'regulation.location.named_street.point_type',
            'label_attr' => [
                'class' => 'required',
            ],
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
