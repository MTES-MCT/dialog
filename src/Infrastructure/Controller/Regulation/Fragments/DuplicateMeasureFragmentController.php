<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DuplicateMeasureCommand;
use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\Measure\GetMeasureByUuidQuery;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Specification\CanDeleteMeasures;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\UX\Turbo\TurboBundle;

final class DuplicateMeasureFragmentController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        Security $security,
        QueryBusInterface $queryBus,
        private CanDeleteMeasures $canDeleteMeasures,
        private CommandBusInterface $commandBus,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/measure/{uuid}/duplicate',
        name: 'fragment_regulations_measure_duplicate',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid('duplicate-measure')]
    public function __invoke(Request $request, string $regulationOrderRecordUuid, string $uuid): Response
    {
        /* On récupère le regulationOrderRecord pour vérifier que l'utilisateur a bien accès à l'organisation */

        $regulationOrderRecord = $this->getRegulationOrderRecord($regulationOrderRecordUuid);

        $measure = $this->queryBus->handle(new GetMeasureByUuidQuery($uuid));

        $generalInfo = $this->queryBus->handle(new GetGeneralInfoQuery($regulationOrderRecordUuid));

        if (!$measure) {
            throw new NotFoundHttpException();
        }

        $this->commandBus->handle(new DuplicateMeasureCommand($measure, $regulationOrderRecord));

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_measure.duplicated.stream.html.twig',
                context: [
                    'measure' => MeasureView::fromEntity($measure),
                    'generalInfo' => $generalInfo,
                    'canDelete' => $this->canDeleteMeasures->isSatisfiedBy($regulationOrderRecord),
                ],
            ),
        );
    }
}
