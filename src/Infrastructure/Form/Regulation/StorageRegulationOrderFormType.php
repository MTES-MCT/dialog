<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

final class StorageRegulationOrderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'file',
                FileType::class,
                options: [
                    'label' => 'regulation.storage',
                    'help' => 'regulation.storage.form.file.help',
                    'required' => false,
                ],
            )
            ->add(
                'url',
                UrlType::class,
                options: [
                    'label' => 'regulation.storage.form.url',
                    'help' => 'regulation.storage.form.url.help',
                    'required' => false,
                ],
            )
            ->add(
                'title',
                TextType::class,
                [
                    'label' => 'regulation.storage.form.title',
                    'required' => false,
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.apply',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }
}
