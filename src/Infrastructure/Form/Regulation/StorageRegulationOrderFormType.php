<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
                    'label' => 'Ajouter un fichier',
                    'help' => 'Taille maximale : 1 Mo. Formats supportés : docx, odt, jpg, pdf.',
                    'required' => false,
                ],
            )
            ->add(
                'url',
                UrlType::class,
                options: [
                    'label' => 'URL du lien',
                    'help' => 'Entrez l’URL vers au format https://www.mondocument.pdf',
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
