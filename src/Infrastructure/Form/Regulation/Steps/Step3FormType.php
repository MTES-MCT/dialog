<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation\Steps;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class Step3FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'startPeriod',
                DateTimeType::class,
                options: [
                    'label' => 'regulation.step3.start_period',
                    'help' => 'regulation.step3.start_period.help',
                    'widget' => 'single_text',
                    'model_timezone' => 'Europe/London',
                    'view_timezone' => 'Europe/Paris',
                ],
            )
            ->add(
                'endPeriod',
                DateTimeType::class,
                options: [
                    'label' => 'regulation.step3.end_period',
                    'help' => 'regulation.step3.end_period.help',
                    'widget' => 'single_text',
                    'model_timezone' => 'Europe/London',
                    'view_timezone' => 'Europe/Paris',
                    'required' => false,
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.next',
                ],
            )
        ;
    }
}
