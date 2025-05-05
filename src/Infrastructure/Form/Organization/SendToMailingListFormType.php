<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class SendToMailingListFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'emails',
                EmailType::class,
                options: [
                    'label' => 'recipient.email',
                    'help' => 'recipient.email.help',
                    'required' => true,
                ],
            )
            ->add(
                'allRecipients',
                CheckboxType::class, [
                    'label' => 'recipient.mailing.list.all',
                    'required' => false,
                ],
            )
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'mailing.list.share',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
