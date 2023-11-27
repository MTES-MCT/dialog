<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\CommandBusInterface;
use App\Application\User\Command\ConvertAccessRequestToUserCommand;
use App\Domain\User\AccessRequest;
use App\Domain\User\Exception\AccessRequestNotFoundException;
use App\Domain\User\Exception\SiretMissingException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class AccessRequestCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AccessRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Création de compte')
            ->setEntityLabelInPlural('Création de comptes')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $convertAccessRequest = Action::new('convertAccessRequest', 'Convertir en compte utilisateur', 'fa fa-user')
            ->displayAsLink()
            ->displayIf(static function ($entity) {
                return $entity->getSiret();
            })
            ->linkToCrudAction('convertAccessRequest')
        ;

        return $actions
            ->add(Crud::PAGE_DETAIL, $convertAccessRequest)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT);
    }

    public function convertAccessRequest(AdminContext $context): RedirectResponse
    {
        $uuid = $context->getEntity()->getPrimaryKeyValue();

        try {
            $this->commandBus->handle(new ConvertAccessRequestToUserCommand($uuid));
            $this->addFlash('success', 'Le compte utilisateur a bien été créé.');

            return $this->redirect(
                $this->adminUrlGenerator
                ->setController(self::class)
                ->setEntityId(null)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl(),
            );
        } catch (AccessRequestNotFoundException) {
            $this->addFlash('error', 'La demande de création de compte n\'existe pas.');
        } catch (SiretMissingException) {
            $this->addFlash('error', 'Le siret est obligatoire.');
        } catch (UserAlreadyRegisteredException) {
            $this->addFlash('error', 'Un utilisateur avec cette adresse email existe déjà.');
        }

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_DETAIL)
            ->setEntityId($uuid)
            ->generateUrl(),
        );
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('fullName')->setLabel('Prénom / Nom'),
            EmailField::new('email'),
            TextField::new('organization')->setLabel('Nom de l\'organisation'),
            TextField::new('siret')->setLabel('Siret'),
            TextField::new('comment')->setLabel('Message')->setDisabled(true),
            BooleanField::new('consentToBeContacted', 'Je souhaite être contacté')->setDisabled(true),
        ];
    }
}
