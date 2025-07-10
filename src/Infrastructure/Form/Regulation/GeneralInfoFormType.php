<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\User\View\UserOrganizationView;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Domain\User\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GeneralInfoFormType extends AbstractType
{
    public function __construct(
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
                'subject',
                ChoiceType::class,
                options: $this->getSubjectOptions(),
            )
            ->add(
                'regulationOrderTemplateUuid',
                ChoiceType::class,
                options: $this->getRegulationOrderTemplates($options['regulationOrderTemplates']),
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
                'title',
                TextareaType::class,
                options: [
                    'label' => 'regulation.general_info.title',
                    'help' => 'regulation.general_info.title.help',
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

    private function getSubjectOptions(): array
    {
        $choices = [
            'regulation.subject.placeholder' => '',
        ];

        foreach (RegulationSubjectEnum::cases() as $case) {
            $choices[\sprintf('regulation.subject.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.general_info.subject',
            'required' => false,
        ];
    }

    private function getRegulationOrderTemplates(array $regulationOrderTemplates = []): array
    {
        $choices = [
            'regulation.general_info.regulation_order_template.placeholder' => '',
        ];

        foreach ($regulationOrderTemplates as $regulationOrderTemplate) {
            $choices[$regulationOrderTemplate->name] = $regulationOrderTemplate->uuid;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.general_info.regulation_order_template',
            'required' => false,
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default', 'html_form'],
            'organizations' => [],
            'regulationOrderTemplates' => [],
            'save_options' => [],
        ]);
        $resolver->setAllowedTypes('organizations', 'array');
        $resolver->setAllowedTypes('regulationOrderTemplates', 'array');
        $resolver->setAllowedTypes('save_options', 'array');
    }
}
