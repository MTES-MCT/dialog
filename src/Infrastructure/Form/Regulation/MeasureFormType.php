<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class MeasureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'type',
                ChoiceType::class,
                options: [
                    'choices' => MeasureTypeEnum::getFormChoices(),
                    'label' => 'regulation.measure.type',
                    'help' => 'regulation.measure.type.help',
                ],
            )
        ;
    }
}
