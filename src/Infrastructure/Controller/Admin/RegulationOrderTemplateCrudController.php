<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\RegulationOrderTemplate;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;

final class RegulationOrderTemplateCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
        private readonly EntityRepository $entityRepository,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Modèle d\'arrêté')
            ->setEntityLabelInPlural('Modèles d\'arrêtés')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setLabel('Nom du modèle'),
            TextareaField::new('title')->setLabel('Titre')->hideOnIndex(),
            TextareaField::new('visaContent')->setLabel('Contenu du visa')->hideOnIndex(),
            TextareaField::new('consideringContent')->setLabel('Contenu de la considération')->hideOnIndex(),
            TextareaField::new('articleContent')->setLabel('Contenu de l\'article')->hideOnIndex(),
        ];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $response = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $response->andWhere('entity.organization IS NULL');

        return $response;
    }

    public function createEntity(string $entityFqcn): RegulationOrderTemplate
    {
        return new RegulationOrderTemplate($this->idFactory->make());
    }

    public static function getEntityFqcn(): string
    {
        return RegulationOrderTemplate::class;
    }
}
