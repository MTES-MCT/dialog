<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;

final class TimeSlotFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('startTime', TimeType::class, [
            'label' => 'regulation.timeSlot.startTime',
            'widget' => 'single_text',
        ])
        ->add('endTime', TimeType::class, [
            'label' => 'regulation.timeSlot.endTime',
            'widget' => 'single_text',
        ])
        ;
    }
}
