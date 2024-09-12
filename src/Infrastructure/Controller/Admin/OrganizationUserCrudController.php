<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\IdFactoryInterface;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\OrganizationUser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

final class OrganizationUserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
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
            ->setDefaultSort(['organization' => 'ASC'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $roles = array_column(OrganizationRolesEnum::cases(), 'value');

        return [
            AssociationField::new('organization')->setLabel('Organisation')->setSortProperty('name'),
            AssociationField::new('user')->setLabel('Utilisateur')->setSortProperty('fullName'),
            ChoiceField::new('roles')
                ->setLabel('Rôles')
                ->setChoices(array_combine($roles, $roles))
                ->renderAsBadges(),
        ];
    }
}
