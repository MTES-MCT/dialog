<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use App\Domain\User\Enum\OrganizationRolesEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roleContributor = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $rolePublisher = OrganizationRolesEnum::ROLE_ORGA_PUBLISHER->value;

        $builder
            ->add(
                'fullName',
                TextType::class,
                options: [
                    'label' => 'user.list.fullName',
                ],
            )
            ->add(
                'email',
                EmailType::class,
                options: [
                    'label' => 'user.list.email',
                    'help' => 'login.email_format',
                ],
            )
            ->add(
                'role',
                ChoiceType::class,
                options: [
                    'choices' => [
                        "roles.$roleContributor" => $roleContributor,
                        "roles.$rolePublisher" => $rolePublisher,
                    ],
                    'label' => 'user.form.roles',
                    'help' => 'user.form.roles.help',
                    'expanded' => true,
                    'multiple' => false,
                ],
            )
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'common.save',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;

        if (!$options['data']->organizationUser) {
            $builder->add(
                'password',
                RepeatedType::class,
                options: [
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'user.form.password'],
                    'second_options' => ['label' => 'user.form.password.repeat'],
                ],
            );
        }
    }
}
