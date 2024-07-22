<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use App\Domain\User\Enum\OrganizationRolesEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class RolesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roleContributor = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $rolePublisher = OrganizationRolesEnum::ROLE_ORGA_PUBLISHER->value;
        $roleAdmin = OrganizationRolesEnum::ROLE_ORGA_ADMIN->value;

        $builder
            ->add(
                'roles',
                ChoiceType::class,
                options: [
                    'choices' => [
                        "roles.$roleContributor" => $roleContributor,
                        "roles.$rolePublisher" => $rolePublisher,
                        "roles.$roleAdmin" => $roleAdmin,
                    ],
                    'label' => 'user.list.roles',
                    'expanded' => true,
                    'multiple' => true,
                ],
            )
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'common.save',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
