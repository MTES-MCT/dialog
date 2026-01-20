<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\User\News;
use App\Infrastructure\Controller\Admin\Common\CommonAdminConfiguration;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

final class NewsNoticeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
        private readonly DateUtilsInterface $dateUtils,
        private readonly CommonAdminConfiguration $commonAdminConfiguration,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Bandeau dernières nouveautés')
            ->setEntityLabelInPlural('Bandeau dernières nouveautés')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->commonAdminConfiguration->configureCommonActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            TextField::new('linkTitle', 'Titre du lien')->hideOnIndex(),
            UrlField::new('link', 'Lien')->hideOnIndex(),
            TextareaField::new('content', 'Contenu'),
            DateTimeField::new('createdAt')->setLabel('Date de création')->hideOnForm(),
        ];
    }

    public function createEntity(string $entityFqcn): News
    {
        $newsNotice = new News($this->idFactory->make());
        $newsNotice->setCreatedAt($this->dateUtils->getNow());

        return $newsNotice;
    }

    public static function getEntityFqcn(): string
    {
        return News::class;
    }
}
