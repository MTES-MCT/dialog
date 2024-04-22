<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Domain\Regulation\Enum\RoadSideEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NumberedRoadFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'administrator',
                ChoiceType::class,
                options: $this->getAdministratorOptions($options['administrators']),
            )
            ->add(
                'roadNumber',
                TextType::class,
                options: [
                    'label' => 'regulation.location.roadNumber',
                    'help' => 'regulation.location.roadNumber.help',
                    'required' => false, // Due to error "An invalid form control with name='x' is not focusable"
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add(
                'fromPointNumber',
                TextType::class,
                options: [
                    'label' => 'regulation.location.referencePoint.pointNumber',
                    'help' => 'regulation.location.referencePoint.pointNumber.help',
                    'required' => false,
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add(
                'fromSide',
                ChoiceType::class,
                options: $this->getRoadSideOptions(),
            )
            ->add(
                'toPointNumber',
                TextType::class,
                options: [
                    'label' => 'regulation.location.referencePoint.pointNumber',
                    'help' => 'regulation.location.referencePoint.pointNumber.help',
                    'required' => false,
                    'label_attr' => [
                        'class' => 'required',
                    ],
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
            ->add('roadType', HiddenType::class)
        ;

        // Constraint "Valid" cannot be nested inside constraint When. The event listener is used to ensure that the roadType is added to the submitted data before the form is processed.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $data['roadType'] = $event->getForm()->getParent()->get('roadType')->getData();
            $event->setData($data);
        });
    }

    private function getRoadSideOptions(): array
    {
        $choices = [];

        foreach (RoadSideEnum::cases() as $case) {
            $choices[sprintf('regulation.location.road.side.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => array_merge(
                $choices,
            ),
            'label' => 'regulation.location.road.side',
            'help' => 'regulation.location.road.side.help',
        ];
    }

    private function getAdministratorOptions(array $administrators): array
    {
        $choices = [];

        foreach ($administrators as $value) {
            $choices[$value] = $value;
        }

        return [
            'label' => 'regulation.location.administrator',
            'label_attr' => [
                'class' => 'required',
            ],
            'required' => false, // Due to error "An invalid form control with name='x' is not focusable"
            'help' => 'regulation.location.administrator.help',
            'choices' => array_merge(
                ['regulation.location.administrator.placeholder' => ''],
                $choices,
            ),
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'administrators' => [],
            'data_class' => SaveNumberedRoadCommand::class,
        ]);
        $resolver->setAllowedTypes('administrators', 'array');
    }
}
