<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation\Steps;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class Step1FormType extends AbstractType
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'startDate',
                DateType::class,
                options: [
                    'label' => 'regulation.step1.start_date',
                    'help' => 'regulation.step1.start_date.help',
                    'widget' => 'single_text',
                ],
            )
            ->add(
                'endDate',
                DateType::class,
                options: [
                    'label' => 'regulation.step1.end_date',
                    'help' => 'regulation.step1.end_date.help',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
                    'required' => false,
                ],
            )
            ->add(
                'issuingAuthority',
                TextType::class,
                options: [
                    'label' => 'regulation.step1.issuing_authority',
                    'help' => 'regulation.step1.issuing_authority.help',
                ],
            )
            ->add(
                'description',
                TextareaType::class,
                options: [
                    'label' => 'regulation.step1.description',
                    'help' => 'regulation.step1.description.help',
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
