<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Condition\SavePeriodCommand;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PeriodFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('applicableDays', ChoiceType::class, $this->getDaysOptions())
            ->add('startTime', TimeType::class, [
                'label' => 'regulation.period.startTime',
                'widget' => 'single_text',
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'regulation.period.endTime',
                'widget' => 'single_text',
            ])
            ->add('includeHolidays', CheckboxType::class, [
                'label' => 'regulation.period.includeHolidays',
                'required' => false,
            ])
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SavePeriodCommand::class,
        ]);
    }
}
