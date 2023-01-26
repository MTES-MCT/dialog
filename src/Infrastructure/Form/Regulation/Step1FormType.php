<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class Step1FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'issuingAuthority',
                TextType::class,
                options: [
                    'label' => 'regulation.step1.issuing_authority',
                    'help' => 'regulation.step1.issuing_authority.help',
                ],
            )
            ->add(
                'description',
                TextType::class,
                options: [
                    'label' => 'regulation.step1.description',
                    'help' => 'regulation.step1.description.help',
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
}
