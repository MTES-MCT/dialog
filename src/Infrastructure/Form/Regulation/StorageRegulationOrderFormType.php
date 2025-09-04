<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\SaveRegulationOrderStorageCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class StorageRegulationOrderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'allRegulationOrderStorage',
                ChoiceType::class,
                options: [
                    'choices' => [
                        'regulation.storage.form.allRegulationOrderStorage.file' => 'file',
                        'regulation.storage.form.allRegulationOrderStorage.url' => 'url',
                    ],
                    'label' => 'regulation.storage.form.allRegulationOrderStorage',
                    'help' => 'regulation.storage.form.allRegulationOrderStorage.help',
                    'expanded' => true,
                    'multiple' => false,
                    'mapped' => false,
                    'data' => 'file',
                ],
            )
            ->add(
                'file',
                FileType::class,
                options: [
                    'label' => 'regulation.storage.form.file.title',
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
                    'label' => 'regulation.storage.form.save',
                    'attr' => ['class' => 'fr-btn'],
                ],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveRegulationOrderStorageCommand::class,
        ]);
    }
}
