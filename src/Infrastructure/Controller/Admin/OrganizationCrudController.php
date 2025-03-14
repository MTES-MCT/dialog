<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\SyncOrganizationAdministrativeBoundariesCommand;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrganizationCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CommandBusInterface $commandBus,
        private readonly OrganizationRepositoryInterface $organizationRepository,
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
            TextField::new('siret')->setLabel('Siret'),
            TextField::new('codeWithType', 'Code territorial')->hideOnForm(),
            DateTimeField::new('updatedAt')->setLabel('Date de dernière mise à jour')->hideOnForm(),
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

    public function configureActions(Actions $actions): Actions
    {
        $syncAllGeometries = Action::new('syncAllGeometries', 'Mettre à jour les contours administratifs')
            ->linkToCrudAction('syncAllGeometries')
            ->setIcon('fa fa-sync')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $syncAllGeometries);
    }

    public function syncAllGeometries(): RedirectResponse
    {
        $organizations = $this->organizationRepository->findAllEntities();
        foreach ($organizations as $organization) {
            $this->commandBus->dispatchAsync(new SyncOrganizationAdministrativeBoundariesCommand($organization->getUuid()));
        }

        $this->addFlash('success', 'La synchronisation des géométries a été lancée en arrière-plan. Cela peut prendre quelques minutes.');

        return $this->redirect($this->urlGenerator->generate('app_admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }
}
