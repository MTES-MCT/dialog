<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\IdFactoryInterface;
use App\Domain\User\Organization;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class OrganizationCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Organisation')
            ->setEntityLabelInPlural('Organisations')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $isEditPage = ($pageName === Crud::PAGE_EDIT);

        $fields = [
            TextField::new('name')->setLabel('Nom de l\'organisation'),
            DateField::new('createdAt')
                ->setLabel('Date de création')
                ->setDisabled($isEditPage),
            TextField::new('siret')->setLabel('Siret'),
            TextField::new('codeWithType', 'Code territorial')->hideOnForm(),
        ];

        if ($isEditPage) {
            $fields[] = TextField::new('uuid')
                ->setLabel('UUID de l\'organisation')
                ->setDisabled(true);
        }

        return $fields;
    }

    public function createEntity(string $entityFqcn): Organization
    {
        return new Organization($this->idFactory->make());
    }

    public static function getEntityFqcn(): string
    {
        return Organization::class;
    }
}
