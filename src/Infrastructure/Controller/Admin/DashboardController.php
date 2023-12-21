<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin;

use App\Domain\User\AccessRequest;
use App\Domain\User\Feedback;
use App\Domain\User\Organization;
use App\Domain\User\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        yield MenuItem::linkToCrud('Organisations', 'fa fa-list', Organization::class);
        yield MenuItem::linkToCrud('Création de comptes', 'fa fa-code-pull-request', AccessRequest::class);
        yield MenuItem::linkToCrud('Avis', 'fa fa-comments', Feedback::class);

        yield MenuItem::section('Application');
        yield MenuItem::linkToRoute('DiaLog', 'fa fa-globe', 'app_regulations_list');
    }
}
