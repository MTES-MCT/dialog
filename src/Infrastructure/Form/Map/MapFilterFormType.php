<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Map;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class MapFilterFormType extends AbstractType
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
                'displayPermanentRegulations',
                CheckboxType::class,
                options: [
                    'label' => 'map.filter.permanents',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add(
                'displayTemporaryRegulations',
                CheckboxType::class,
                options: [
                    'label' => 'map.filter.temporaries',
                    'value' => 'yes',
                    'required' => false,
                ],
            )
            ->add(
                'startDate',
                DateType::class,
                options: [
                    'label' => 'map.filter.start_date',
                    'help' => 'map.filter.start_date.help',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
                    'required' => false,
                ],
            )
            ->add(
                'endDate',
                DateType::class,
                options: [
                    'label' => 'map.filter.end_date',
                    'help' => 'map.filter.end_date.help',
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
            $choices[\sprintf('map.filter.type.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'map.filters.title.type.restriction',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ];
    }
}
