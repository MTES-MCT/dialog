<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Organization;

use App\Application\Organization\MailingList\Command\SendRegulationOrderToMailingListCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SendToMailingListFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'emails',
                TextareaType::class,
                options: [
                    'label' => 'recipient.form.email',
                    'help' => 'recipient.form.email.help',
                    'required' => false,
                ],
            )
            ->add(
                'recipients',
                ChoiceType::class,
                [
                    'label' => 'recipient.mailing.list.all',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => $this->getRecipients($options['recipients']),
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'mailing.list.share',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }

    private function getRecipients(array $recipients): array
    {
        $choices = [];

        foreach ($recipients as $recipient) {
            $choices[\sprintf('%s (%s)', $recipient['name'], $recipient['email'])] = \sprintf('%s#%s', $recipient['name'], $recipient['email']);
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SendRegulationOrderToMailingListCommand::class,
            'recipients' => [],
        ]);
        $resolver->setAllowedTypes('recipients', 'array');
    }
}
