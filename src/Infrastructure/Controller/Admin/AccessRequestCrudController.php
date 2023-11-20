<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\User\AccessRequest;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AccessRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AccessRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Création de compte')
            ->setEntityLabelInPlural('Création de comptes')
        ;
    }

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
            TextField::new('fullName')->setLabel('Prénom / Nom'),
            EmailField::new('email'),
            TextField::new('organization')->setLabel('Nom de l\'organisation'),
            TextField::new('siret')->setLabel('Siret'),
            TextField::new('comment')->setLabel('Message')->setDisabled(true),
            BooleanField::new('consentToBeContacted', 'Je souhaite être contacté')->setDisabled(true),
        ];
    }
}
