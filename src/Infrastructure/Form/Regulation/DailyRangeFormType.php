<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DailyRangeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('applicableDays', ChoiceType::class, $this->getDaysOptions())
            ->add('recurrenceType', HiddenType::class)
        ;

        // Constraint "Valid" cannot be nested inside constraint When. The event listener is used to ensure that the recurrenceType is added to the submitted data before the form is processed.

        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
                $data = $event->getData();
                $recurrenceType = $event->getForm()->getParent()->get('recurrenceType')->getData();
                $data['recurrenceType'] = $recurrenceType;
                $event->setData($data);
            });
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
