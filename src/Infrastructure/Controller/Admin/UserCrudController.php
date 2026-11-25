<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Command\Mail\SendAccountValidatedEmailCommand;
use App\Application\User\Command\SetUserAccountStatusCommand;
use App\Application\User\Query\GetAllUsersForExportQuery;
use App\Domain\User\Enum\UserAccountStatusEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Controller\Admin\Common\CommonAdminConfiguration;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UserCrudController extends AbstractCrudController
{
    private const ACCOUNT_STATUS_CSRF_TOKEN_PREFIX = 'admin-user-account-status-';

    public function __construct(
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly IdFactoryInterface $idFactory,
        private readonly PasswordUserRepositoryInterface $passwordUserRepository,
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CommonAdminConfiguration $commonAdminConfiguration,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createEntity(string $entityFqcn): User
    {
        return (new User($this->idFactory->make()))
            ->setAccountStatus(UserAccountStatusEnum::VALIDATED->value);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['registrationDate' => 'DESC'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $statuses = array_column(UserAccountStatusEnum::cases(), 'value');

        return $filters
            ->add(ChoiceFilter::new('accountStatus')
                ->setLabel('Statut du compte')
                ->setChoices(array_combine($statuses, $statuses)));
    }

    public function configureActions(Actions $actions): Actions
    {
        $exportCsv = Action::new('exportCsv', 'Exporter les utilisateurs en CSV')
            ->linkToCrudAction('exportCsv')
            ->setIcon('fa fa-download')
            ->createAsGlobalAction();

        $validateAccount = Action::new('validateUserAccount', 'Valider le compte')
            ->linkToUrl(fn (User $user): string => $this->generateAccountStatusActionUrl('validateUserAccount', $user))
            ->renderAsForm()
            ->setIcon('fa fa-check')
            ->displayIf(static function (User $user): bool {
                return $user->getAccountStatus() !== UserAccountStatusEnum::VALIDATED->value;
            });

        $refuseAccount = Action::new('refuseUserAccount', 'Refuser le compte')
            ->linkToUrl(fn (User $user): string => $this->generateAccountStatusActionUrl('refuseUserAccount', $user))
            ->renderAsForm()
            ->setIcon('fa fa-ban')
            ->displayIf(static function (User $user): bool {
                return $user->getAccountStatus() !== UserAccountStatusEnum::REFUSED->value;
            });

        $this->commonAdminConfiguration->configureCommonActions($actions);

        return $actions
            ->add(Crud::PAGE_INDEX, $exportCsv)
            ->add(Crud::PAGE_INDEX, $validateAccount)
            ->add(Crud::PAGE_INDEX, $refuseAccount)
        ;
    }

    public function validateUserAccount(AdminContext $context): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        $this->denyUnlessValidAccountStatusRequest($context, $user);

        $wasAlreadyValidated = $user->getAccountStatus() === UserAccountStatusEnum::VALIDATED->value;

        $response = $this->changeAccountStatus($context, UserAccountStatusEnum::VALIDATED, 'Le compte a été validé.');

        if (!$wasAlreadyValidated) {
            $this->commandBus->dispatchAsync(new SendAccountValidatedEmailCommand($user->getEmail(), $user->getFullName()));
        }

        return $response;
    }

    public function refuseUserAccount(AdminContext $context): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        $this->denyUnlessValidAccountStatusRequest($context, $user);

        return $this->changeAccountStatus($context, UserAccountStatusEnum::REFUSED, 'Le compte a été refusé.');
    }

    private function generateAccountStatusActionUrl(string $crudAction, User $user): string
    {
        return $this->adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction($crudAction)
            ->setEntityId($user->getUuid())
            ->set('_token', $this->container->get('security.csrf.token_manager')->getToken(self::ACCOUNT_STATUS_CSRF_TOKEN_PREFIX . $user->getUuid())->getValue())
            ->generateUrl();
    }

    private function denyUnlessValidAccountStatusRequest(AdminContext $context, User $user): void
    {
        $request = $context->getRequest();

        if (!$request->isMethod('POST')) {
            throw new MethodNotAllowedHttpException(['POST']);
        }

        if (!$this->isCsrfTokenValid(self::ACCOUNT_STATUS_CSRF_TOKEN_PREFIX . $user->getUuid(), (string) $request->query->get('_token'))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    private function changeAccountStatus(AdminContext $context, UserAccountStatusEnum $status, string $successMessage): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();

        $this->commandBus->handle(new SetUserAccountStatusCommand($user->getUuid(), $status->value));
        $this->addFlash('success', $successMessage);

        $fallbackUrl = $context->getReferrer()
            ?? $this->urlGenerator->generate('app_admin', [
                'crudAction' => 'index',
                'crudControllerFqcn' => self::class,
            ]);

        return $this->redirect($fallbackUrl);
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
            BooleanField::new('isVerified')
                ->setLabel('Actif'),
            ChoiceField::new('accountStatus')
                ->setLabel('Statut du compte')
                ->setChoices(array_combine(
                    array_column(UserAccountStatusEnum::cases(), 'value'),
                    array_column(UserAccountStatusEnum::cases(), 'value'),
                ))
                ->renderAsBadges([
                    UserAccountStatusEnum::PENDING->value => 'warning',
                    UserAccountStatusEnum::VALIDATED->value => 'success',
                    UserAccountStatusEnum::REFUSED->value => 'danger',
                ]),
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
