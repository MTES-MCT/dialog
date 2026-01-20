<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\Common;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

final readonly class CommonAdminConfiguration
{
    public function configureCommonActions(Actions $actions): Actions
    {
        return $actions->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, static fn (Action $action) => $action->setLabel('Enregistrer')->setIcon('fa fa-save'))
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, static fn (Action $action) => $action->setLabel('Enregistrer et quitter')->setIcon('fa fa-sign-out'))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, static fn (Action $action) => $action->setLabel('Enregistrer')->setIcon('fa fa-save'))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, static fn (Action $action) => $action->setLabel('Enregistrer et quitter')->setIcon('fa fa-sign-out'))
        ;
    }
}
