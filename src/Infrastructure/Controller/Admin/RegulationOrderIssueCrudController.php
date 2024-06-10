<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\Regulation\RegulationOrderIssue;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RegulationOrderIssueCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RegulationOrderIssue::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('source')->setLabel('Origine'),
            TextField::new('identifier')->setLabel('Identifiant'),
            TextField::new('organization')->setLabel('Organisation'),
            TextField::new('context')->setLabel('Information'),
            ChoiceField::new('level')
                ->setLabel('Niveau')
                ->setChoices(['warning' => 'warning', 'error' => 'error'])
                ->renderAsBadges([
                    'warning' => 'warning',
                    'error' => 'danger',
                ]),
            DateTimeField::new('createdAt')->setLabel('Créé le'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Alerte qualité')
            ->setEntityLabelInPlural('Alertes qualité')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
        ;
    }
}
