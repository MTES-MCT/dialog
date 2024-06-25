<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Specification\CanUseRawGeoJSON;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LocationFormType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

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

    private function isRawGeoJSONDisabled(?string $value, array $options): bool
    {
        return $value === RoadTypeEnum::RAW_GEOJSON->value && !\in_array(CanUseRawGeoJSON::PERMISSION_NAME, $options['permissions']);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['readonly'] = false;

        if ($this->isRawGeoJSONDisabled($form->get('roadType')->getData(), $options)) {
            $view->vars['readonly'] = true;
            $label = $form->get('rawGeoJSON')->get('label')->getData();
            $view->vars['readonly_text'] = $label . ' (' . strtolower($this->translator->trans('regulation.location.road.type.rawGeoJSON')) . ')';
        }
    }

    private function getRoadTypeOptions(array $options): array
    {
        $choices = [];
        $choiceAttr = [];

        foreach (RoadTypeEnum::cases() as $case) {
            $label = sprintf('regulation.location.road.type.%s', $case->value);

            if ($this->isRawGeoJSONDisabled($case->value, $options)) {
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
            'permissions' => [],
            'data_class' => SaveLocationCommand::class,
        ]);
        $resolver->setAllowedTypes('administrators', 'array');
        $resolver->setAllowedTypes('permissions', 'array');
    }
}
