<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class LocationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'address',
                TextType::class,
                options: [
                    'label' => 'regulation.location.address',
                ],
            )
            ->add(
                'fromHouseNumber',
                TextType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.location.from_house_number',
                ],
            )
            ->add(
                'toHouseNumber',
                TextType::class,
                options: [
                    'required' => false,
                    'label' => 'regulation.location.to_house_number',
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
