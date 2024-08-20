<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Tools\Litteralis;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class GeometrySearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'arretesrcid',
                TextType::class,
                options: [
                    'label' => 'tools.litteralis.geometry_search.arretesrcid',
                    'help' => 'tools.litteralis.geometry_search.arretesrcid.help',
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.search',
                ],
            )
        ;
    }
}
