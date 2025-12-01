<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\User;

use App\Application\User\Command\SaveReportAddressCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ReportAddressFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'roadType',
                TextType::class,
                options: [
                    'label' => 'localisation du problème',
                ],
            )
            ->add(
                'content',
                TextareaType::class,
                options: [
                    'label' => 'report_address.content.label',
                    'help' => 'report_address.content.help',
                ],
            )
            ->add('save', SubmitType::class,
                options: [
                    'label' => 'regulation.location.report.problem.send',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveReportAddressCommand::class,
        ]);
    }
}
