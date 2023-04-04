<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation\Steps;

use App\Application\Regulation\Command\Steps\SaveRegulationStep2Command;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class Step2FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'address',
                TextType::class,
                options: [
                    'label' => 'regulation.step2.address',
                ],
            )
            ->add(
                'fromHouseNumber',
                TextType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.step2.from_house_number',
                ],
            )
            ->add(
                'toHouseNumber',
                TextType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.step2.to_house_number',
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.next',
                ],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveRegulationStep2Command::class,
        ]);
    }
}
