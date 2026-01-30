<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Infrastructure\Controller\Admin\Common\CommonAdminConfiguration;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;

final class RegulationOrderTemplateCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
        private readonly EntityRepository $entityRepository,
        private readonly DateUtilsInterface $dateUtils,
        private readonly CommonAdminConfiguration $commonAdminConfiguration,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Modèle d\'arrêté')
            ->setEntityLabelInPlural('Modèles d\'arrêtés')
            ->setFormThemes(['admin/form.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
        ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addWebpackEncoreEntry('quill');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->commonAdminConfiguration->configureCommonActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setLabel('Nom du modèle'),
            TextareaField::new('title')
                ->setLabel('Titre')
                ->hideOnIndex()
                ->setFormTypeOptions(['block_name' => 'rich_textarea_title']),
            TextareaField::new('visaContent')
                ->setLabel('Contenu du visa')
                ->hideOnIndex()
                ->setFormTypeOptions(['block_name' => 'rich_textarea_visa']),
            TextareaField::new('consideringContent')
                ->setLabel('Contenu de la considération')
                ->hideOnIndex()
                ->setFormTypeOptions(['block_name' => 'rich_textarea_considering']),
            TextareaField::new('articleContent')
                ->setLabel('Contenu de l\'article')
                ->hideOnIndex()
                ->setFormTypeOptions(['block_name' => 'rich_textarea_article']),
            DateTimeField::new('createdAt')->setLabel('Date de création')->hideOnForm(),
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
        $regulationOrderTemplate = new RegulationOrderTemplate($this->idFactory->make());
        $regulationOrderTemplate->setCreatedAt($this->dateUtils->getNow());

        return $regulationOrderTemplate;
    }

    public static function getEntityFqcn(): string
    {
        return RegulationOrderTemplate::class;
    }
}
