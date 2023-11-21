<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DailyRangeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveDailyRangeCommand::class,
        ]);
    }
}
