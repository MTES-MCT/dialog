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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                'visaModelUuid',
                ChoiceType::class,
                options: $this->getVisaModels($options['visaModels']),
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
                'entitled',
                TextareaType::class,
                options: [
                    'label' => 'regulation.general_info.entitled',
                    'help' => 'regulation.general_info.entitled.help',
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
                        'label' => 'regulation.general_info.additional_visas.entry',
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
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                [$this, 'onPreSubmit'],
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

    /**
     * Cette méthode met à jour les valeurs de l'input en fonction du résultat de l'API,
     * afin d'éviter l'erreur "Le choix sélectionné est invalide".
     */
    public function onPreSubmit(FormEvent $event): void
    {
        $input = $event->getData()['visaModelUuid'];
        $event->getForm()->add('visaModelUuid', ChoiceType::class, ['choices' => [$input]]);
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

    private function getVisaModels(array $visaModels = []): array
    {
        $choices = [
            'regulation.general_info.visa_model.placeholder' => '',
            'DiaLog' => [],
        ];

        foreach ($visaModels as $visaModel) {
            $organizationName = $visaModel->organizationUuid ? $visaModel->organizationName : 'DiaLog';
            $choices[$organizationName][$visaModel->name] = $visaModel->uuid;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.general_info.visa_model',
            'help' => 'regulation.general_info.visa_model.help',
            'required' => false,
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default', 'html_form'],
            'organizations' => [],
            'visaModels' => [],
            'save_options' => [],
        ]);
        $resolver->setAllowedTypes('organizations', 'array');
        $resolver->setAllowedTypes('visaModels', 'array');
        $resolver->setAllowedTypes('save_options', 'array');
    }
}
