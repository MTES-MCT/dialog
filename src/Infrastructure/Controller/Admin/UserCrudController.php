<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetAllUsersForExportQuery;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Controller\Admin\Common\CommonAdminConfiguration;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Response;

final class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly IdFactoryInterface $idFactory,
        private readonly PasswordUserRepositoryInterface $passwordUserRepository,
        private readonly QueryBusInterface $queryBus,
        private readonly CommonAdminConfiguration $commonAdminConfiguration,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createEntity(string $entityFqcn): User
    {
        return new User($this->idFactory->make());
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['uuid' => 'ASC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $exportCsv = Action::new('exportCsv', 'Exporter les utilisateurs en CSV')
            ->linkToCrudAction('exportCsv')
            ->setIcon('fa fa-download')
            ->createAsGlobalAction();

        $this->commonAdminConfiguration->configureCommonActions($actions);

        return $actions
            ->add(Crud::PAGE_INDEX, $exportCsv)
        ;
    }

    public function exportCsv(): Response
    {
        $csv = $this->queryBus->handle(new GetAllUsersForExportQuery());

        return new Response($csv, headers: [
            'Content-Type' => 'text/csv; charset=ISO-8859-1',
            'Content-Disposition' => 'attachment; filename="Utilisateurs_DiaLog_' . date('Y-m-d_His') . '.csv"',
        ]);
    }

    public function configureFields(string $pageName): iterable
    {
        $roles = array_column(UserRolesEnum::cases(), 'value');

        $fields = [
            TextField::new('fullName')->setLabel('Prénom / Nom'),
            EmailField::new('email'),
            DateField::new('registrationDate')
                ->setLabel('Date d\'inscription')
                ->setDisabled($pageName === Crud::PAGE_EDIT),
            DateField::new('lastActiveAt')
                ->setLabel('Dernière activité')
                ->setDisabled(true),
            ChoiceField::new('roles')
                ->setLabel('Rôles')
                ->allowMultipleChoices()
                ->setChoices(array_combine($roles, $roles))
                ->renderAsBadges(),
        ];

        $password = TextField::new('password')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Répéter le mot de passe'],
                'mapped' => false,
            ])
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms();

        $fields[] = $password;

        return $fields;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        return $this->addPasswordEventListener($formBuilder);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        return $this->addPasswordEventListener($formBuilder);
    }

    private function addPasswordEventListener(FormBuilderInterface $formBuilder): FormBuilderInterface
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
            $form = $event->getForm();
            $data = $form->getData();

            if (!$form->isValid()) {
                return;
            }

            $password = $form->get('password')->getData();
            if ($password) {
                $hashedPassword = $this->passwordHasher->hash($password);

                if ($passwordUser = $data->getPasswordUser()) {
                    $passwordUser->setPassword($hashedPassword);

                    return;
                }

                $passwordUser = new PasswordUser(
                    uuid: $this->idFactory->make(),
                    password: $hashedPassword,
                    user: $data,
                );
                $this->passwordUserRepository->add($passwordUser);
                $data->setPasswordUser($passwordUser);
            }
        });
    }
}
