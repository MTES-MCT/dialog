<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PeriodFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('startHour', TimeType::class, [
                'label' => 'regulation.period.startTime',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('endHour', TimeType::class, [
                'label' => 'regulation.period.endTime',
                'widget' => 'single_text',
            ])
            ->add('recurrenceType', ChoiceType::class,
                options: $this->getRecurrenceTypeOptions(),
            )
            ->add('applicableDays', ChoiceType::class, $this->getDaysOptions())
        ;
    }

    private function getDaysOptions(): array
    {
        $choices = [];

        foreach (ApplicableDayEnum::cases() as $case) {
            $choices[sprintf('regulation.period.days.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'expanded' => true,
            'multiple' => true,
            'label' => 'regulation.period.days',
            'help' => 'regulation.period.days.help',
        ];
    }

    private function getRecurrenceTypeOptions(): array
    {
        $choices = [];

        foreach (PeriodRecurrenceTypeEnum::cases() as $case) {
            $choices[sprintf('regulation.period.recurrenceType.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.period.recurrenceType',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SavePeriodCommand::class,
        ]);
    }
}
