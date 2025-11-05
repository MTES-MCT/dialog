<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\User\News;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class NewsNoticeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
        private readonly DateUtilsInterface $dateUtils,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular(' Bandeau dernières nouveautés')
            ->setEntityLabelInPlural('Bandeau dernières nouveautés')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            TextField::new('title', 'Titre')->setFormTypeOption('disabled', true)->hideOnIndex(),
            TextareaField::new('content', 'Contenu'),
            DateTimeField::new('createdAt')->setLabel('Date de création')->hideOnForm(),
        ];
    }

    public function createEntity(string $entityFqcn): News
    {
        $newsNotice = new News($this->idFactory->make());
        $newsNotice->setTitle('Découvrez les dernières nouveautés sur DiaLog !');
        $newsNotice->setCreatedAt($this->dateUtils->getNow());

        return $newsNotice;
    }

    public static function getEntityFqcn(): string
    {
        return News::class;
    }
}
