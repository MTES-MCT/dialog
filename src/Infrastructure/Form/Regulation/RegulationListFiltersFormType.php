<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\User\View\OrganizationView;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Enum\RegulationOrderTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RegulationListFiltersFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'identifier',
                SearchType::class,
                options: [
                    'label' => 'regulation.list.filters.identifier',
                    'attr' => [
                        'placeholder' => 'regulation.list.filters.identifier.placeholder',
                    ],
                    'required' => false,
                ],
            )
            ->add(
                'organizationUuid',
                ChoiceType::class,
                options: [
                    'label' => 'regulation.list.filters.organizationUuid',
                    'placeholder' => 'regulation.list.filters.organizationUuid.placeholder',
                    'choices' => $options['organizations'],
                    'choice_value' => 'uuid',
                    'choice_label' => 'name',
                    'required' => false,
                ],
            )
            ->add(
                'regulationOrderType',
                ChoiceType::class,
                options: $this->getRegulationOrderTypeOptions(),
            )
            ->add(
                'status',
                ChoiceType::class,
                options: $this->getStatusOptions($options['user']),
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.apply',
                ],
            )
        ;

        $builder->get('organizationUuid')
            ->addModelTransformer(new CallbackTransformer(
                function (?OrganizationView $organization): ?OrganizationView {
                    return $organization;
                },
                function (?OrganizationView $organizationView): ?string {
                    return $organizationView?->uuid;
                },
            ))
        ;
    }

    private function getRegulationOrderTypeOptions(): array
    {
        $choices = [
            'regulation.list.filters.regulationOrderType.placeholder' => '',
        ];

        foreach (RegulationOrderTypeEnum::cases() as $case) {
            $choices[\sprintf('regulation.list.filters.regulationOrderType.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.list.filters.regulationOrderType',
            'required' => false,
        ];
    }

    private function getStatusOptions(?object $user): array
    {
        $choices = [
            'regulation.list.filters.status.placeholder' => '',
        ];

        foreach (RegulationOrderRecordStatusEnum::cases() as $case) {
            $choices[\sprintf('regulation.list.filters.status.%s', $case->value)] = $case->value;
        }

        $options = [
            'choices' => $choices,
            'label' => 'regulation.list.filters.status',
            'required' => false,
        ];

        if (!$user) {
            $options = array_merge($options, [
                'row_attr' => [
                    'class' => 'fr-hidden',
                ],
            ]);
        }

        return $options;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
            'organizations' => [],
        ]);
        $resolver->setAllowedTypes('user', ['null', 'object']);
        $resolver->setAllowedTypes('organizations', 'array');
    }
}
