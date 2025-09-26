<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Domain\Organization\ApiClient;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

final class ApiClientCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IdFactoryInterface $idFactory,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly DateUtilsInterface $dateUtils,
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
            TextField::new('clientId')->setLabel('Client ID')->setTemplatePath('admin/field/copy_badge.html.twig')->setDisabled(true),
            TextField::new('clientSecret')->setLabel('Client Secret')->setTemplatePath('admin/field/copy_badge.html.twig')->setDisabled(true),
            DateTimeField::new('createdAt')->setLabel('Date de création')->hideOnForm(),
        ];

        return $fields;
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

            $apiClient
                ->setClientId($this->idFactory->make())
                ->setClientSecret($this->passwordHasher->hash($this->idFactory->make()))
                ->setCreatedAt($this->dateUtils->getNow());
        });

        return $formBuilder;
    }
}
