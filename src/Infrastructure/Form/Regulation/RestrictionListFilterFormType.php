<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class RestrictionListFilterFormType extends AbstractType
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'measureTypes',
                ChoiceType::class,
                options: $this->getMeasureTypesOptions(),
            )
            ->add(
                'isPermanent',
                CheckboxType::class,
                options: [
                    'label' => 'restriction.list.filter.isPermanent',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add(
                'isTemporary',
                CheckboxType::class,
                options: [
                    'label' => 'restriction.list.filter.isTemporary',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add(
                'startDate',
                DateType::class,
                options: [
                    'label' => 'restriction.list.filter.start_date',
                    'help' => 'restriction.list.filter.start_date.help',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
                    'required' => false,
                ],
            )
            ->add(
                'endDate',
                DateType::class,
                options: [
                    'label' => 'restriction.list.filter.end_date',
                    'help' => 'restriction.list.filter.end_date.help',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
                    'required' => false,
                ],
            )
            ->add('save', SubmitType::class)
        ;
    }

    private function getMeasureTypesOptions(): array
    {
        $choices = [];

        foreach (MeasureTypeEnum::cases() as $case) {
            $choices[\sprintf('regulation.measure.type.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'restriction.list.filter.measureTypes.title',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ];
    }
}
