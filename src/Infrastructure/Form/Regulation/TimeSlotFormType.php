<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TimeSlotFormType extends AbstractType
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startTime', TimeType::class, [
                'label' => 'regulation.timeSlot.startTime',
                'widget' => 'choice',
                'view_timezone' => $this->clientTimezone,
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'regulation.timeSlot.endTime',
                'widget' => 'choice',
                'view_timezone' => $this->clientTimezone,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveTimeSlotCommand::class,
        ]);
    }
}
