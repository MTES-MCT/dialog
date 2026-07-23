<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Application\DateUtilsInterface;
use App\Domain\User\ReportAddress;
use App\Infrastructure\Adapter\IgnReportClient;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class ReportAddressCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly IgnReportClient $ignReportClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly DateUtilsInterface $dateUtils,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendToIgn = Action::new('sendToIgn', 'Envoyer à l\'IGN')
            ->setIcon('fa fa-paper-plane fa-sm')
            ->linkToCrudAction('sendToIgn')
            ->displayIf(static fn (ReportAddress $report): bool => $report->getIgnReportId() === null);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $sendToIgn)
            ->add(Crud::PAGE_DETAIL, $sendToIgn)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT);
    }

    public function sendToIgn(AdminContext $context): RedirectResponse
    {
        /** @var ReportAddress $report */
        $report = $context->getEntity()->getInstance();

        $fallbackUrl = $context->getReferrer()
            ?? $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl();

        if ($report->getIgnReportId() !== null) {
            $this->addFlash('warning', 'Ce signalement a déjà été envoyé à l\'IGN.');

            return $this->redirect($fallbackUrl);
        }

        $geometry = $report->getIgnGeometry();
        if ($geometry === null || trim($geometry) === '') {
            $this->addFlash('danger', 'Impossible d\'envoyer le signalement à l\'IGN : aucune géométrie disponible.');

            return $this->redirect($fallbackUrl);
        }

        $result = $this->ignReportClient->submitReport($report->getContent(), $geometry);

        if ($result === null) {
            $this->addFlash('danger', 'L\'envoi du signalement à l\'IGN a échoué. Consultez les logs pour plus de détails.');

            return $this->redirect($fallbackUrl);
        }

        $report->setIgnReportId($result->id);
        $report->setIgnReportStatus($result->status);
        $report->setIgnStatusUpdatedAt($this->dateUtils->getNow());
        $this->entityManager->flush();

        $this->addFlash('success', \sprintf('Signalement envoyé à l\'IGN avec succès (ID : %s).', $result->id));

        return $this->redirect($fallbackUrl);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('user.fullName', 'Prénom / Nom'),
            EmailField::new('user.email', 'Email'),
            TextField::new('location', 'Localisation'),
            TextareaField::new('content', 'Signalement adresse'),
            TextField::new('ignReportId', 'ID signalement IGN')
                ->hideOnForm()
                ->formatValue(static function (?string $id): string {
                    if ($id === null || $id === '') {
                        return '—';
                    }
                    $url = 'https://espacecollaboratif.ign.fr/georem/' . rawurlencode($id);
                    $escaped = htmlspecialchars($id, \ENT_QUOTES, 'UTF-8');

                    return \sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', $url, $escaped);
                })
                ->renderAsHtml(),
            TextField::new('ignReportStatus', 'Statut IGN')->hideOnForm(),
            DateTimeField::new('ignStatusUpdatedAt', 'Dernière MAJ statut IGN')->hideOnForm(),
            TextField::new('ignGeometry', 'Géométrie (WKT)')->hideOnForm()->hideOnIndex(),
            BooleanField::new('hasBeenContacted', 'A été contacté'),
            DateTimeField::new('createdAt')->setLabel('Date de création')->hideOnForm(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Signalement adresse')
            ->setEntityLabelInPlural('Signalements adresse')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public static function getEntityFqcn(): string
    {
        return ReportAddress::class;
    }
}
