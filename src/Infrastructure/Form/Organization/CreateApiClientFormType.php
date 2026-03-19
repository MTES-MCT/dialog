<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Organization;

use App\Application\User\View\OrganizationUserView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CreateApiClientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var OrganizationUserView[] $users */
        $users = $options['users'];
        $choices = [];
        foreach ($users as $user) {
            $label = $user->fullName . ' (' . $user->email . ')';
            $choices[$label] = $user->uuid;
        }

        $builder
            ->add('user', ChoiceType::class, [
                'label' => 'api_client.form.user',
                'choices' => $choices,
                'placeholder' => 'api_client.form.user_placeholder',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'api_client.create.submit',
                'attr' => ['class' => 'fr-btn'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['users', 'organization_uuid']);
        $resolver->setAllowedTypes('users', 'array');
        $resolver->setAllowedTypes('organization_uuid', 'string');
    }
}
