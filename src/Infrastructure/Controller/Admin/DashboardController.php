<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\Organization\ApiClient;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\User\Feedback;
use App\Domain\User\News;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('DiaLog');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Gestion des utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Membres d\'organisations', 'fa fa-user-gear', OrganizationUser::class);

        yield MenuItem::section('Gestion des organisations');
        yield MenuItem::linkToCrud('Organisations', 'fa fa-list', Organization::class);
        yield MenuItem::linkToCrud('Accès API', 'fa fa-key', ApiClient::class);

        yield MenuItem::section('Configuration des arrếtés');
        yield MenuItem::linkToCrud('Modèles d\'arrêtés', 'fa fa-book', RegulationOrderTemplate::class);

        yield MenuItem::section('Autres');
        yield MenuItem::linkToCrud('Avis', 'fa fa-comments', Feedback::class);

        yield MenuItem::linkToCrud('Bandeaux nouveautés', 'fa fa-newspaper', News::class);
    }
}
