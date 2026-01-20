<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\Organization\ApiClient;
use App\Domain\User\TokenGenerator;
use App\Infrastructure\Controller\Admin\Common\CommonAdminConfiguration;
use App\Infrastructure\Security\User\ApiClientUser;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class ApiClientCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly DateUtilsInterface $dateUtils,
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly CommonAdminConfiguration $commonAdminConfiguration,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Accès API')
            ->setEntityLabelInPlural('Accès API')
            ->setDefaultSort(['organization.name' => 'ASC'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            AssociationField::new('organization')->setLabel('Organisation')->setSortProperty('name'),
            TextField::new('clientId')->setLabel('Client ID')->setDisabled(true),
            BooleanField::new('isActive')->setLabel('Actif'),
            DateTimeField::new('createdAt')->setLabel('Date de création')->hideOnForm(),
            DateTimeField::new('lastUsedAt')->setLabel('Date de dernière utilisation')->hideOnForm(),
        ];

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        $this->commonAdminConfiguration->configureCommonActions($actions);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::new('regenerateApiAccess', 'Régénérer le secret')
            ->linkToCrudAction('regenerateApiAccess'))
        ;
    }

    public function createEntity(string $entityFqcn): ApiClient
    {
        return new ApiClient($this->idFactory->make());
    }

    public static function getEntityFqcn(): string
    {
        return ApiClient::class;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
            $form = $event->getForm();

            if (!$form->isValid()) {
                return;
            }

            /** @var ApiClient */
            $apiClient = $form->getData();
            $plainSecret = (new TokenGenerator())->generate();
            $hashedSecret = $this->passwordHasherFactory->getPasswordHasher(ApiClientUser::class)->hash($plainSecret);
            $apiClient
                ->setClientId($this->idFactory->make())
                ->setClientSecret($hashedSecret)
                ->setCreatedAt($this->dateUtils->getNow());

            $this->addFlash(
                'success',
                \sprintf(
                    'Accès créé avec succès (copiez-les maintenant) !<br/>Client ID : <strong>%s</strong><br>Client secret : <strong>%s</strong>',
                    $apiClient->getClientId(),
                    $plainSecret,
                ),
            );
        });

        return $formBuilder;
    }

    public function regenerateApiAccess(AdminContext $context): RedirectResponse
    {
        $entity = $context->getEntity()->getInstance();

        $fallbackUrl = $context->getReferrer()
            ?? $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl();

        $plainSecret = (new TokenGenerator())->generate();
        $hashedSecret = $this->passwordHasherFactory->getPasswordHasher(ApiClientUser::class)->hash($plainSecret);
        $entity->setClientSecret($hashedSecret);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            \sprintf(
                'Accès modifié avec succès (copiez-les maintenant) !<br/>Client ID : <strong>%s</strong><br>Client secret : <strong>%s</strong>',
                $entity->getClientId(),
                $plainSecret,
            ),
        );

        return $this->redirect($fallbackUrl);
    }
}
