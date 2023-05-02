<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Measure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MeasureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'measureType',
                ChoiceType::class,
                options: [
                    'choices' => array_column(MeasureTypeEnum::cases(), 'value'),
                    'label' => 'regulation.measure.type',
                    'help' => 'regulation.measure.type.help',
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.validate',
                ],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveMeasureCommand::class,
        ]);
    }
}
