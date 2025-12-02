<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DeleteMeasureCommand;
use App\Application\Regulation\Query\Measure\GetMeasureByUuidQuery;
use App\Domain\Regulation\Exception\MeasureCannotBeDeletedException;
use App\Domain\Regulation\Specification\CanDeleteMeasures;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\UX\Turbo\TurboBundle;

final class DeleteMeasureFragmentController extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private CommandBusInterface $commandBus,
        private CanDeleteMeasures $canDeleteMeasures,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        Security $security,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/_fragment/regulations/{regulationOrderRecordUuid}/measure/{uuid}/delete',
        name: 'fragment_regulations_measure_delete',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    #[IsCsrfTokenValid('delete-measure')]
    public function __invoke(Request $request, string $regulationOrderRecordUuid, string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($regulationOrderRecordUuid);

        $measure = $this->queryBus->handle(new GetMeasureByUuidQuery($uuid));
        if (!$measure) {
            throw new NotFoundHttpException();
        }

        try {
            $this->commandBus->handle(new DeleteMeasureCommand($measure));
        } catch (MeasureCannotBeDeletedException) {
            throw new BadRequestHttpException();
        }

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_measure.deleted.stream.html.twig',
                context: [
                    'uuid' => $uuid,
                    'canDelete' => $this->canDeleteMeasures->isSatisfiedBy($regulationOrderRecord),
                    'measureUuids' => $regulationOrderRecord->getMeasureUuids(),
                ],
            ),
        );
    }
}
