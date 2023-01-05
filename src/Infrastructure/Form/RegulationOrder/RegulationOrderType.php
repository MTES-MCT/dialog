<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\RegulationOrder;

use App\Application\RegulationOrder\Command\CreateRegulationOrderCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RegulationOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'description',
                TextType::class,
                options: [
                    'label' => 'regulation_order.description',
                ],
            )
            ->add(
                'issuingAuthority',
                TextType::class,
                options: [
                    'label' => 'regulation_order.issuing_authority',
                ],
            )
            ->add(
                'startPeriod',
                DateType::class,
                options: [
                    'label' => 'regulation_order.form.start_period',
                    'widget' => 'single_text',
                ],
            )
            ->add(
                'endPeriod',
                DateType::class,
                options: [
                    'label' => 'regulation_order.form.end_period',
                    'widget' => 'single_text',
                    'required' => false,
                ],
            )
            ->add(
                'maxWeight',
                NumberType::class,
                options: [
                    'label' => 'regulation_order.form.max_weight',
                    'required' => false,
                ],
            )
            ->add(
                'maxHeight',
                NumberType::class,
                options: [
                    'label' => 'regulation_order.form.max_height',
                    'required' => false,
                ],
            )
            ->add(
                'maxWidth',
                NumberType::class,
                options: [
                    'label' => 'regulation_order.form.max_width',
                    'required' => false,
                ],
            )
            ->add(
                'maxLength',
                NumberType::class,
                options: [
                    'label' => 'regulation_order.form.max_length',
                    'required' => false,
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.save',
                ],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateRegulationOrderCommand::class,
        ]);
    }
}
