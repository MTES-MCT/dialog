<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Domain\Regulation\Enum\DirectionEnum;
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
                TextType::class,
                options: [
                    'label' => 'regulation.location.referencePoint.pointNumber',
                    'help' => 'regulation.location.referencePoint.pointNumber.help',
                ],
            )
            ->add(
                'toPointNumber',
                TextType::class,
                options: [
                    'label' => 'regulation.location.referencePoint.pointNumber',
                    'help' => 'regulation.location.referencePoint.pointNumber.help',
                ],
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

        // Constraint "Valid" cannot be nested inside constraint When. The event listener is used to ensure that the roadType is added to the submitted data before the form is processed.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $data['roadType'] = $event->getForm()->getParent()->get('roadType')->getData();
            $data['direction'] = $data['direction'] ?? DirectionEnum::BOTH->value;
            $event->setData($data);
        });
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
            'data_class' => SaveNumberedRoadCommand::class,
        ]);
        $resolver->setAllowedTypes('roadType', 'string');
        $resolver->setAllowedTypes('administrators', 'array');
    }
}
