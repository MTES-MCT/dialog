<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\IdFactoryInterface;
use App\Domain\User\OrganizationUser;
use App\Infrastructure\Controller\Admin\Common\CommonAdminConfiguration;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

final class OrganizationUserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
        private readonly CommonAdminConfiguration $commonAdminConfiguration,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return OrganizationUser::class;
    }

    public function createEntity(string $entityFqcn): OrganizationUser
    {
        return new OrganizationUser($this->idFactory->make());
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Membre d\'organisation')
            ->setEntityLabelInPlural('Membres d\'organisations')
            ->setSearchFields(['organization.name', 'user.fullName'])
            ->setDefaultSort(['organization' => 'ASC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->commonAdminConfiguration->configureCommonActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('organization')->setLabel('Organisation')->setSortProperty('name'),
            AssociationField::new('user')->setLabel('Utilisateur')->setSortProperty('fullName'),
            BooleanField::new('isOwner')->setLabel('Propriétaire'),
        ];
    }
}
