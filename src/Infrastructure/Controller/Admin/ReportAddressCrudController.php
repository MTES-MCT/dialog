<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\User\ReportAddress;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ReportAddressCrudController extends AbstractCrudController
{
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('user.fullName', 'Prénom / Nom'),
            EmailField::new('user.email', 'Email'),
            TextField::new('location', 'Localisation'),
            TextField::new('content', 'Signalement adresse'),
            TextField::new('ignReportId', 'ID signalement IGN')
                ->hideOnForm()
                ->formatValue(static function (?string $id): string {
                    if ($id === null || $id === '') {
                        return '—';
                    }
                    $url = 'https://espacecollaboratif.ign.fr/georem/' . rawurlencode($id);
                    $escaped = htmlspecialchars($id, \ENT_QUOTES, 'UTF-8');

                    return \sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', $url, $escaped);
                })
                ->renderAsHtml(),
            TextField::new('ignReportStatus', 'Statut IGN')->hideOnForm(),
            DateTimeField::new('ignStatusUpdatedAt', 'Dernière MAJ statut IGN')->hideOnForm(),
            BooleanField::new('hasBeenContacted', 'A été contacté'),
            DateTimeField::new('createdAt')->setLabel('Date de création')->hideOnForm(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Signalement adresse')
            ->setEntityLabelInPlural('Signalements adresse')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public static function getEntityFqcn(): string
    {
        return ReportAddress::class;
    }
}
