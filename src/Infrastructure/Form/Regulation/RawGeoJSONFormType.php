<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RawGeoJSONFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'label',
                TextType::class,
                options: [
                    'label' => 'regulation.location.raw_geojson.label',
                ],
            )
            ->add(
                'geometry',
                TextareaType::class,
                options: [
                    'label' => 'regulation.location.raw_geojson.geometry',
                ],
            )
            ->add('roadType', HiddenType::class)
        ;

        // Seulement au niveau localisation : ce form type est aussi embarqué dans le
        // sous-formulaire d'exception (WholeCityExceptionFormType), qui ne doit pas
        // proposer d'exceptions imbriquées.
        if ($options['with_exceptions']) {
            $builder->add(
                'exceptions',
                CollectionType::class,
                options: [
                    'entry_type' => WholeCityExceptionFormType::class,
                    'entry_options' => ['label' => false, 'with_city' => true],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype' => true,
                    'prototype_name' => '__exception_name__',
                    // Pré-sélectionne « Voie » dans le prototype pour qu'une exception ajoutée
                    // affiche directement son sous-formulaire.
                    'prototype_data' => new SaveWholeCityExceptionCommand(),
                    'label' => false,
                ],
            );
        }

        // Constraint "Valid" cannot be nested inside constraint When. The event listener is used to ensure that the roadType is added to the submitted data before the form is processed.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $data['roadType'] = $event->getForm()->getParent()->get('roadType')->getData();
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'with_exceptions' => false,
            'data_class' => SaveRawGeoJSONCommand::class,
        ]);
        $resolver->setAllowedTypes('with_exceptions', 'bool');
    }
}
