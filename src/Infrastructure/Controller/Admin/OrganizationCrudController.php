<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\SyncOrganizationAdministrativeBoundariesCommand;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
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
        $codeTypes = array_column(OrganizationCodeTypeEnum::cases(), 'value');

        $fields = [
            TextField::new('name')->setLabel('Nom de l\'organisation'),
            TextField::new('siret')->setLabel('Siret'),
            TextField::new('code')->setLabel('Code territorial')->hideOnIndex(),
            ChoiceField::new('codeType')
                ->setLabel('Type de code')
                ->setChoices(array_combine($codeTypes, $codeTypes))
                ->hideOnIndex(),
            TextField::new('codeWithType', 'Code territorial')->hideOnForm(),
            DateTimeField::new('updatedAt')->setLabel('Date de dernière mise à jour')->hideOnForm(),
        ];

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

        $showMap = Action::new('showMap', 'Voir la carte')
            ->linkToCrudAction('showMap')
            ->displayIf(static function (Organization $organization): bool {
                return null !== $organization->getGeometry();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $syncAllGeometries)
            ->add(Crud::PAGE_INDEX, $showMap);
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

    public function showMap(): Response
    {
        $adminContext = $this->getContext();
        $organizationId = $adminContext->getRequest()->query->get('entityId');
        $organization = $this->organizationRepository->findOneByUuid($organizationId);

        if (!$organization || !$organization->getGeometry()) {
            $this->addFlash('danger', 'Organisation ou géométrie non trouvée.');

            return $this->redirect($this->urlGenerator->generate('app_admin', [
                'crudAction' => 'index',
                'crudControllerFqcn' => self::class,
            ]));
        }

        return $this->render('admin/organization/map.html.twig', [
            'organization' => $organization,
            'geometry' => $organization->getGeometry(),
        ]);
    }
}
