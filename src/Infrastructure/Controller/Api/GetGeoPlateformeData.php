<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetGeoPlateformeDataQuery;
use App\Infrastructure\Form\GeoPlateformeDataFormType;
use App\Infrastructure\Geoplateforme\Geoplateforme;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GetGeoPlateformeData
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private FormFactoryInterface $formFactory,
        private Geoplateforme $geoplateforme,
    ) {
    }

    #[Route(
        '/api/geodata',
        methods: ['GET', 'POST'],
        name: 'api_geo_plateforme_list',
    )]

    public function __invoke(): Response
    {
        
        $geodata = $this->geoplateforme->fetchInformations();
        dd($geodata);

        $form = $this->formFactory->create(GeoPlateformeDataFormType::class);
        return new Response(
            $this->twig->render(
                name: 'api/geodata.twig',
                context: [
                    'form' => $form->createView(), 
                ]
                ),
        );
    }
}
