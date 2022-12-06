<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\RegulationOrder;

use App\Application\RegulationOrder\Command\CreateRegulationOrderCommand;
use Symfony\Component\Form\AbstractType;
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
                    'label' => 'regulation_order.form.description',
                ],
            )
            ->add(
                'issuingAuthority',
                TextType::class,
                options: [
                    'label' => 'regulation_order.form.issuing_authority',
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
