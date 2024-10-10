<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\User\View\UserOrganizationView;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\User\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GeneralInfoFormType extends AbstractType
{
    public function __construct(
        private string $clientTimezone,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'identifier',
                TextType::class,
                options: [
                    'label' => 'regulation.general_info.identifier',
                    'help' => 'regulation.general_info.identifier.help',
                ],
            )
            ->add(
                'startDate',
                DateType::class,
                options: [
                    'label' => 'regulation.general_info.start_date',
                    'help' => 'regulation.general_info.start_date.help',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
                ],
            )
            ->add(
                'endDate',
                DateType::class,
                options: [
                    'label' => 'regulation.general_info.end_date',
                    'help' => 'regulation.general_info.end_date.help',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
                    'required' => false,
                ],
            )
            ->add(
                'organization',
                ChoiceType::class,
                options: [
                    'label' => 'regulation.general_info.organization',
                    'help' => 'regulation.general_info.organization.help',
                    'choices' => $options['organizations'],
                    'choice_value' => 'uuid',
                    'choice_label' => 'name',
                ],
            )
            ->add(
                'category',
                ChoiceType::class,
                options: $this->getCategoryOptions(),
            )
            ->add(
                'otherCategoryText',
                TextType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.general_info.other_category_text',
                    'label_attr' => [
                        'class' => 'required',
                    ],
                ],
            )
            ->add(
                'description',
                TextareaType::class,
                options: [
                    'label' => 'regulation.general_info.description',
                    'help' => 'regulation.general_info.description.help',
                ],
            )
            ->add(
                'additionalVisas',
                CollectionType::class,
                options: [
                    'entry_type' => TextareaType::class,
                    'label' => null,
                    'prototype_name' => '__visa_name__',
                    'entry_options' => [
                        'label' => 'regulation.general_info.visa',
                    ],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'keep_as_list' => true,
                    'error_bubbling' => false,
                ],
            )
            ->add(
                'additionalReasons',
                CollectionType::class,
                options: [
                    'entry_type' => TextareaType::class,
                    'label' => null,
                    'prototype_name' => '__reason_name__',
                    'entry_options' => [
                        'label' => 'regulation.general_info.reason',
                    ],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'keep_as_list' => true,
                    'error_bubbling' => false,
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: $options['save_options'],
            )
        ;

        $builder->get('organization')
            ->addModelTransformer(new CallbackTransformer(
                function (?Organization $organization = null): ?UserOrganizationView {
                    return $organization
                        ? new UserOrganizationView($organization->getUuid(), $organization->getName())
                        : null;
                },
                function (UserOrganizationView $view): Organization {
                    return $this->entityManager->getReference(Organization::class, $view->uuid);
                },
            ))
        ;
    }

    private function getCategoryOptions(): array
    {
        $choices = [
            'regulation.category.placeholder' => '',
        ];

        foreach (RegulationOrderCategoryEnum::cases() as $case) {
            $choices[\sprintf('regulation.category.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.general_info.category',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default', 'html_form'],
            'organizations' => [],
            'save_options' => [],
        ]);
        $resolver->setAllowedTypes('organizations', 'array');
        $resolver->setAllowedTypes('save_options', 'array');
    }
}
