<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LocationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'roadType',
                ChoiceType::class,
                options: $this->getRoadTypeOptions($options),
            )
            ->add('numberedRoad', NumberedRoadFormType::class, [
                'administrators' => $options['administrators'],
                'label' => false,
            ])
            ->add('namedStreet', NamedStreetFormType::class, [
                'label' => false,
            ])
            ->add('rawGeoJSON', RawGeoJSONFormType::class, [
                'label' => false,
            ])
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['readonly'] = false;

        if ($form->get('roadType')->getData() === RoadTypeEnum::RAW_GEOJSON->value && !$options['canUseRawGeoJSON']) {
            $view->vars['readonly'] = true;
            $view->vars['readonly_text'] = $form->get('rawGeoJSON')->get('label')->getData();
        }
    }

    private function getRoadTypeOptions(array $options): array
    {
        $choices = [];
        $choiceAttr = [];

        foreach (RoadTypeEnum::cases() as $case) {
            $label = sprintf('regulation.location.road.type.%s', $case->value);

            if ($case->value === RoadTypeEnum::RAW_GEOJSON->value && !$options['canUseRawGeoJSON']) {
                $choiceAttr[$label] = ['hidden' => ''];
            }

            $choices[$label] = $case->value;
        }

        return [
            'choices' => array_merge(
                ['regulation.location.type.placeholder' => ''],
                $choices,
            ),
            'choice_attr' => $choiceAttr,
            'label' => 'regulation.location.type',
            'label_attr' => [
                'class' => 'required',
            ],
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'administrators' => [],
            'canUseRawGeoJSON' => false,
            'data_class' => SaveLocationCommand::class,
        ]);
        $resolver->setAllowedTypes('administrators', 'array');
        $resolver->setAllowedTypes('canUseRawGeoJSON', 'boolean');
    }
}
