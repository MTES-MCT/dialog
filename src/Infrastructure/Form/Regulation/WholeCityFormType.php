<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WholeCityFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'cityCode',
                HiddenType::class,
            )
            ->add(
                'cityLabel',
                TextType::class,
                options: [
                    'label' => 'regulation.location.city',
                ],
            )
            ->add(
                'exceptions',
                CollectionType::class,
                options: [
                    'entry_type' => WholeCityExceptionFormType::class,
                    'entry_options' => ['label' => false],
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
            )
            ->add('roadType', HiddenType::class)
        ;

        // Constraint "Valid" cannot be nested inside constraint When. The event listener is used to ensure that the roadType is added to the submitted data before the form is processed.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $data['roadType'] = $event->getForm()->getParent()->get('roadType')->getData();
            $event->setData($data);
        });

        // Les exceptions de type voie héritent la ville de la « Ville entière » : on injecte
        // cityCode/cityLabel côté serveur (sans dépendre du JS) pour la validation et le calcul
        // de géométrie de chaque exception.
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $command = $event->getData();

            if (!$command instanceof SaveWholeCityCommand) {
                return;
            }

            foreach ($command->exceptions as $exception) {
                if ($exception->roadType === RoadTypeEnum::LANE->value && $exception->namedStreet) {
                    $exception->namedStreet->cityCode = $command->cityCode;
                    $exception->namedStreet->cityLabel = $command->cityLabel;
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['Default', 'html_form'],
            'data_class' => SaveWholeCityCommand::class,
            'error_mapping' => [
                'cityCode' => 'cityLabel',
            ],
        ]);
    }
}
